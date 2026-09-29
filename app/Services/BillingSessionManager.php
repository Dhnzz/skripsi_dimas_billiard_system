<?php

namespace App\Services;

use App\Models\Addon;
use App\Models\Billing;
use App\Models\BillingAddon;
use App\Models\Booking;
use App\Models\Package;
use App\Models\Pricing;
use App\Models\Table;
use App\Exceptions\DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class BillingSessionManager
{
    /**
     * Start a new billiard billing session.
     *
     * @param array $data
     * @return Billing
     * @throws DomainException
     */
    public function start(array $data): Billing
    {
        return DB::transaction(function () use ($data) {
            // If booking_id is provided, load the booking and populate fields
            $booking = null;
            if (!empty($data['booking_id'])) {
                $booking = Booking::lockForUpdate()->findOrFail($data['booking_id']);
                if (!$booking->isConfirmed()) {
                    throw new DomainException('Pemesanan harus dikonfirmasi terlebih dahulu.');
                }
                if ($booking->billing()->exists()) {
                    throw new DomainException('Billing untuk pemesanan ini sudah dibuat.');
                }
                
                $data['customer_id'] = $booking->customer_id;
                $data['table_id'] = $booking->table_id;
                $data['package_id'] = $booking->package_id;
                $data['pricing_id'] = $booking->pricing_id;
                $data['guest_name'] = $booking->guest_name ?: ($booking->customer?->name ?? 'Member');
            }

            $tableId = $data['table_id'] ?? null;
            if (!$tableId) {
                throw new DomainException('Meja harus dipilih.');
            }

            // Lock Table for update to prevent concurrent allocation
            $table = Table::where('id', $tableId)->lockForUpdate()->first();
            if (!$table) {
                throw new DomainException('Meja tidak ditemukan.');
            }

            // If starting from booking today, table status might be 'occupied' in advance, so allow occupied if it's the booking's table and we are converting it
            // Wait, in confirmBooking in booking/show.blade.php:
            // "if ($this->booking->scheduled_date?->isToday()) { $this->booking->table?->update(['status' => 'occupied']); }"
            // So if starting from a booking, the table status could already be 'occupied'.
            // Let's verify if there is an active billing on this table. If not, then it is available for this booking.
            $hasActiveBilling = Billing::where('table_id', $tableId)
                ->where('status', 'active')
                ->exists();

            if ($hasActiveBilling) {
                throw new DomainException('Meja tidak tersedia lagi (sedang digunakan oleh billing aktif).');
            }

            if (!$booking && $table->status !== 'available') {
                throw new DomainException('Meja tidak tersedia lagi. Silakan pilih meja lain.');
            }

            $now = now();
            $packageId = $data['package_id'] ?? null;
            $pricingId = $data['pricing_id'] ?? null;

            // Handle test durations if package_id indicates test
            $isTest = false;
            $scheduledEndAt = null;

            if ($packageId && str_starts_with($packageId, 'test_')) {
                $isTest = true;
                if ($packageId === 'test_10s') {
                    $scheduledEndAt = $now->copy()->addSeconds(10)->addSeconds(3);
                } else {
                    $mins = (int) str_replace('test_', '', $packageId);
                    $scheduledEndAt = $now->copy()->addMinutes($mins)->addSeconds(3);
                }
                $packageId = Package::where('type', 'normal')->where('duration_hours', 1)->first()?->id;
            }

            $pkg = $packageId ? Package::with('pricing')->find($packageId) : null;

            if ($booking && (!$pkg || !$pkg->isNormal())) {
                if ($booking->scheduled_start && $booking->scheduled_end) {
                    $start = Carbon::parse($booking->scheduled_start);
                    $end = Carbon::parse($booking->scheduled_end);
                    $diffInMinutes = $start->diffInMinutes($end);
                    if ($diffInMinutes < 0) $diffInMinutes += 1440; // Over midnight
                    $scheduledEndAt = $now->copy()->addMinutes($diffInMinutes);
                }
            } elseif (!$isTest && $pkg && $pkg->isNormal()) {
                $scheduledEndAt = $now->copy()->addHours((float) $pkg->duration_hours);
            }

            $finalPricingId = null;
            if ($pkg && $pkg->isLoss() && $pkg->pricing_id) {
                $finalPricingId = $pkg->pricing_id;
            } elseif ($pricingId) {
                $finalPricingId = $pricingId;
            }

            $billing = Billing::create([
                'booking_id'       => $booking?->id,
                'customer_id'      => $data['customer_id'] ?? null,
                'guest_name'       => trim($data['guest_name']),
                'table_id'         => $tableId,
                'package_id'       => $pkg?->id,
                'pricing_id'       => $finalPricingId,
                'started_at'       => $now,
                'ended_at'         => $now, // Placeholder, as ended_at is not-nullable in database
                'scheduled_end_at' => $scheduledEndAt,
                'status'           => 'active',
                'started_by'       => Auth::id(),
                'notes'            => trim($data['notes'] ?? '') ?: null,
            ]);

            // Addons
            $addonTotal = 0;
            $selectedAddons = $data['selectedAddons'] ?? [];
            foreach ($selectedAddons as $key => $val) {
                if (is_array($val) && isset($val['id'])) {
                    $addonId = $val['id'];
                    $qty = $val['qty'];
                    $price = $val['price'];
                    $subtotal = $val['subtotal'];
                } else {
                    $addonId = $key;
                    $qty = $val;
                    $addon = Addon::find($addonId);
                    if (!$addon) continue;
                    $price = (float) $addon->price;
                    $subtotal = $price * $qty;
                }

                BillingAddon::create([
                    'billing_id'        => $billing->id,
                    'addon_id'          => $addonId,
                    'quantity'          => $qty,
                    'unit_price'        => $price,
                    'subtotal'          => $subtotal,
                    'status'            => 'confirmed',
                    'requested_by'      => Auth::id(),
                    'requested_by_role' => 'kasir',
                    'confirmed_by'      => Auth::id(),
                    'confirmed_at'      => now(),
                ]);
                $addonTotal += $subtotal;
            }

            if ($addonTotal > 0) {
                $billing->update(['addon_total' => $addonTotal]);
            }

            $table->update(['status' => 'occupied', 'device_status' => true]);
            
            // Broadcast table status updated
            try {
                broadcast(new \App\Events\TableStatusUpdated($table->id));
            } catch (\Throwable $e) {
                report($e);
            }

            return $billing;
        });
    }

    /**
     * Extend an active billing session.
     *
     * @param Billing $billing
     * @param int $hours
     * @return void
     * @throws DomainException
     */
    public function extend(Billing $billing, int $hours): void
    {
        if ($hours < 1) {
            throw new DomainException('Minimal perpanjangan 1 jam.');
        }

        DB::transaction(function () use ($billing, $hours) {
            if (!$billing->isActive()) {
                throw new DomainException('Hanya billing aktif yang dapat diperpanjang.');
            }

            if (!$billing->scheduled_end_at) {
                throw new DomainException('Hanya billing dengan batas waktu yang dapat diperpanjang.');
            }

            // Lock the Table to prevent race conditions
            $table = Table::where('id', $billing->table_id)->lockForUpdate()->first();
            if (!$table) {
                throw new DomainException('Meja tidak ditemukan.');
            }

            $newEnd = $billing->scheduled_end_at->copy()->addHours($hours);

            // Check conflict with upcoming bookings
            $isWalkIn = empty($billing->booking_id);
            $conflict = Booking::where('table_id', $billing->table_id)
                ->whereIn('status', ['confirmed', 'pending'])
                ->when(!$isWalkIn, fn($q) => $q->where('id', '!=', $billing->booking_id))
                ->whereDate('scheduled_date', '>=', today())
                ->get()
                ->contains(function ($bk) use ($newEnd) {
                    if (!$bk->scheduled_start) return false;
                    $ubStart = Carbon::parse(
                        $bk->scheduled_date->format('Y-m-d') . ' ' . $bk->scheduled_start
                    );
                    return $newEnd->greaterThan($ubStart);
                });

            if ($conflict) {
                throw new DomainException('Ada booking lain yang menempati meja ini di jam tersebut.');
            }

            $billing->update(['scheduled_end_at' => $newEnd]);

            // Sync to booking if exists
            if ($billing->booking) {
                $billing->booking->update(['scheduled_end' => $newEnd->format('H:i:s')]);
            }

            // Fetch pricing schemas
            $pkg = $billing->package;
            $pricing = $billing->pricing ?? $pkg?->pricing;
            $pricePerHour = (float)($pricing?->price_per_hour ?? 0);

            // Record a Time Extension
            \App\Models\BillingTimeExtension::create([
                'billing_id'           => $billing->id,
                'added_hours'          => $hours,
                'price_per_hour'       => $pricePerHour,
                'total_price'          => $hours * $pricePerHour,
                'extended_by'          => Auth::id(),
                'new_scheduled_end_at' => $newEnd,
            ]);

            // Turn table lamp back on if it was off due to time expired
            if (!$table->device_status) {
                $table->update(['device_status' => true]);
                try {
                    broadcast(new \App\Events\TableStatusUpdated($table->id));
                } catch (\Throwable $e) {
                    report($e);
                }
            }

            // Broadcast updates
            try {
                broadcast(new \App\Events\BillingUpdated($billing->id));
            } catch (\Throwable $e) {
                report($e);
            }
        });
    }

    /**
     * Finish a billing session and record payment.
     *
     * @param Billing $billing
     * @param string $paymentMethod
     * @param float|null $amountPaid
     * @param bool $auto
     * @return void
     * @throws DomainException
     */
    public function finish(Billing $billing, string $paymentMethod, ?float $amountPaid = null, bool $auto = false): void
    {
        DB::transaction(function () use ($billing, $paymentMethod, $amountPaid, $auto) {
            if (!$billing->isActive()) {
                return;
            }

            // If not auto-finish, validate paid amount for cash
            if (!$auto) {
                if ($paymentMethod === 'cash') {
                    if ($amountPaid === null || $amountPaid < 0) {
                        throw new DomainException('Nominal uang yang diterima harus diisi.');
                    }
                    if ($amountPaid < $billing->current_total) {
                        throw new DomainException('Uang yang diterima kurang dari total tagihan.');
                    }
                }
            }

            $pkg = $billing->package;
            $pricing = $billing->pricing ?? $pkg?->pricing;
            $end = now();

            // Lock meter at scheduled_end_at to prevent overcharging
            if ($billing->scheduled_end_at && $end->greaterThan($billing->scheduled_end_at)) {
                $end = $billing->scheduled_end_at;
            }

            $elapsedSeconds = $billing->started_at->diffInSeconds($end);
            $elapsedHours   = max(1, (int) floor($elapsedSeconds / 3600));
            $basePrice      = 0;
            $extraPrice     = 0;

            if (!$pkg) {
                $basePrice  = $elapsedHours * (float)($pricing?->price_per_hour ?? 0);
            } elseif ($pkg->isNormal()) {
                $basePrice  = (float) $pkg->price;
                $extraHrs   = max(0, $elapsedHours - (int) $pkg->duration_hours);
                $extraPrice = $extraHrs * (float)($pricing?->price_per_hour ?? 0);
            } else {
                // loss package
                $basePrice = $elapsedHours * (float)($pricing?->price_per_hour ?? 0);
            }

            $addonTotal = $billing->confirmedAddons()->sum('subtotal');
            $grandTotal = $basePrice + $extraPrice + $addonTotal;

            $billing->update([
                'status'                => 'completed',
                'ended_at'              => $end,
                'actual_duration_hours' => $elapsedHours,
                'base_price'            => $basePrice,
                'extra_price'           => $extraPrice,
                'addon_total'           => $addonTotal,
                'grand_total'           => $grandTotal,
                'ended_by'              => Auth::id(),
            ]);

            // Save payment record
            $amountPaidFinal  = ($paymentMethod === 'cash') ? (float) $amountPaid : (float) $grandTotal;
            $changeAmountFinal = ($paymentMethod === 'cash') ? max(0, (float) $amountPaid - (float) $grandTotal) : 0;

            \App\Models\Payment::create([
                'billing_id'    => $billing->id,
                'customer_id'   => $billing->customer_id,
                'guest_name'    => $billing->guest_name,
                'amount'        => $grandTotal,
                'amount_paid'   => $amountPaidFinal,
                'change_amount' => $changeAmountFinal,
                'method'        => $auto ? 'cash' : $paymentMethod,
                'status'        => 'paid',
                'paid_at'       => now(),
                'processed_by'  => Auth::id(),
            ]);

            // Release table
            $table = Table::where('id', $billing->table_id)->lockForUpdate()->first();
            if ($table) {
                $table->update(['status' => 'available', 'device_status' => false]);
                try {
                    broadcast(new \App\Events\TableStatusUpdated($table->id));
                } catch (\Throwable $e) {
                    report($e);
                }
            }

            // Complete booking if associated
            if ($billing->booking) {
                $billing->booking->update(['status' => 'completed']);
            }

            try {
                broadcast(new \App\Events\BillingUpdated($billing->id));
            } catch (\Throwable $e) {
                report($e);
            }
        });
    }
}
