<?php

use App\Models\Billing;
use Livewire\Volt\Component;

new class extends \Livewire\Component {
    public function checkBillings()
    {
        $now = now();
        $fiveMinsLater = now()->addMinutes(5);
        $tenMinsLater = now()->addMinutes(10);

        // 1. Auto-end expired billings
        $expiredBillings = Billing::with(['package.pricing', 'pricing', 'addons', 'table', 'booking'])
            ->where('status', 'active')
            ->whereNotNull('scheduled_end_at')
            ->where('scheduled_end_at', '<=', $now)
            ->get();

        foreach ($expiredBillings as $billing) {
            $this->autoFinishBilling($billing);

            // Notify cashier
            $this->dispatch('notify', [
                'message' => 'Billing ' . $billing->billing_code . ' (Meja ' . ($billing->table->table_number ?? '-') . ') telah otomatis diselesaikan.',
                'type' => 'info'
            ]);
            
            $this->dispatch('billing-ended'); // to refresh other components if needed
        }

        // 2. Notify for billings ending soon
        $soonBillings = Billing::where('status', 'active')
            ->whereNotNull('scheduled_end_at')
            ->where('scheduled_end_at', '>', $now)
            ->where('scheduled_end_at', '<=', $tenMinsLater->copy()->addMinute())
            ->get();

        foreach ($soonBillings as $billing) {
            $diffMins = $now->diffInMinutes($billing->scheduled_end_at, false);
            
            if ($diffMins == 10 || $diffMins == 5 || $diffMins == 1) {
                $this->dispatch('notify', [
                    'message' => 'Billing ' . $billing->billing_code . ' (Meja ' . ($billing->table->table_number ?? '-') . ') akan habis dalam ' . $diffMins . ' menit!',
                    'type' => 'warning'
                ]);
            }
        }
    }

    private function autoFinishBilling(Billing $billing)
    {
        $pkg     = $billing->package;
        $pricing = $billing->pricing ?? $pkg?->pricing;
        $end     = $billing->scheduled_end_at; // use the exact scheduled time

        $elapsedSeconds = $billing->started_at->diffInSeconds($end);
        $elapsedHours   = max(1, (int) floor($elapsedSeconds / 3600));
        $basePrice      = 0;
        $extraPrice     = 0;

        if (!$pkg) {
            $basePrice  = $elapsedHours * (float)($pricing?->price_per_hour ?? 0);
        } elseif ($pkg->type === 'normal') {
            $basePrice  = (float) $pkg->price;
            $extraHrs   = max(0, $elapsedHours - (int) $pkg->duration_hours);
            $extraPrice = $extraHrs * (float)($pricing?->price_per_hour ?? 0);
        } else {
            $basePrice = $elapsedHours * (float)($pricing?->price_per_hour ?? 0);
        }

        $addonTotal = $billing->addons()->where('status', 'confirmed')->sum('subtotal');
        $grandTotal = $basePrice + $extraPrice + $addonTotal;

        $billing->update([
            'status'                => 'completed',
            'ended_at'              => $end,
            'actual_duration_hours' => $elapsedHours,
            'base_price'            => $basePrice,
            'extra_price'           => $extraPrice,
            'addon_total'           => $addonTotal,
            'grand_total'           => $grandTotal,
            'ended_by'              => auth()->id(),
        ]);

        \App\Models\Payment::create([
            'billing_id'    => $billing->id,
            'customer_id'   => $billing->customer_id,
            'guest_name'    => $billing->guest_name,
            'amount'        => $grandTotal,
            'amount_paid'   => $grandTotal, // Auto consider paid exact amount as cash initially (since it's auto-end) or they update later
            'change_amount' => 0,
            'method'        => 'cash',
            'status'        => 'paid',
            'paid_at'       => now(),
            'processed_by'  => auth()->id(),
        ]);

        // Update status meja: available & device_status OFF (lampu mati)
        if ($billing->table) {
            $billing->table->update(['status' => 'available', 'device_status' => false]);
            broadcast(new \App\Events\TableStatusUpdated($billing->table->id));
        }

        broadcast(new \App\Events\BillingUpdated($billing->id));

        if ($billing->booking) {
            $billing->booking->update(['status' => 'completed']);
        }
    }
};
?>

<div wire:poll.10s="checkBillings">
    {{-- This component silently monitors and auto-ends billings --}}
</div>
