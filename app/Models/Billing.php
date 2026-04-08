<?php

namespace App\Models;

use App\Models\BillingAddon;
use App\Models\BillingTimeExtension;
use App\Models\Booking;
use App\Models\Package;
use App\Models\Payment;
use App\Models\Pricing;
use App\Models\Table;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class Billing extends Model
{
    protected $fillable = [
        'billing_code',
        'booking_id',
        'customer_id',
        'guest_name',
        'table_id',
        'package_id',
        'pricing_id',
        'started_at',
        'scheduled_end_at',
        'ended_at',
        'extra_duration_hours',
        'actual_duration_hours',
        'base_price',
        'extra_price',
        'addon_total',
        'grand_total',
        'status',
        'started_by',
        'ended_by',
        'notes'
    ];

    protected $casts = [
        'started_at'          => 'datetime',
        'scheduled_end_at'    => 'datetime',
        'ended_at'            => 'datetime',
        'extra_duration_hours' => 'decimal:2',
        'actual_duration_hours' => 'decimal:2',
        'base_price'          => 'decimal:2',
        'extra_price'         => 'decimal:2',
        'addon_total'         => 'decimal:2',
        'discount_amount'     => 'decimal:2',
        'grand_total'         => 'decimal:2',
    ];

    // GENERATE BILLING CODE
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($billing) {
            if (empty($billing->billing_code)) {
                $date   = now()->format('Ymd');
                $latest = self::whereDate('created_at', today())->count() + 1;
                $billing->billing_code = 'BL-' . $date . '-' . str_pad($latest, 3, '0', STR_PAD_LEFT);
            }
        });
    }

    // RELATIONS   

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }
    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id');
    }
    public function table()
    {
        return $this->belongsTo(Table::class);
    }
    public function package()
    {
        return $this->belongsTo(Package::class);
    }
    public function pricing()
    {
        return $this->belongsTo(Pricing::class);
    }
    public function startedBy()
    {
        return $this->belongsTo(User::class, 'started_by');
    }
    public function endedBy()
    {
        return $this->belongsTo(User::class, 'ended_by');
    }
    public function payment()
    {
        return $this->hasOne(Payment::class);
    }

    public function addons()
    {
        return $this->hasMany(BillingAddon::class);
    }

    public function confirmedAddons()
    {
        return $this->hasMany(BillingAddon::class)->where('status', 'confirmed');
    }

    public function pendingAddons()
    {
        return $this->hasMany(BillingAddon::class)->where('status', 'pending');
    }

    public function timeExtensions()
    {
        return $this->hasMany(BillingTimeExtension::class);
    }

    // COMPUTED : RUNNING TIME

    public function getElapsedSecondsAttribute(): int
    {
        $end = $this->isActive() ? now() : $this->ended_at;

        // Jika billing aktif dan waktu sudah melewati jadwal habis, maka argometer dikunci berhenti di jadwal habis
        if ($this->isActive() && $this->scheduled_end_at && $end->greaterThan($this->scheduled_end_at)) {
            $end = $this->scheduled_end_at;
        }

        return (int) $this->started_at->diffInSeconds($end);
    }

    /** Durasi berjalan diformat HH:MM:SS */
    public function getElapsedFormattedAttribute(): string
    {
        $hours = floor($this->elapsed_seconds / 3600);
        $minutes = floor(($this->elapsed_seconds / 60) % 60);
        $seconds = $this->elapsed_seconds % 60;

        return sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
    }

    /** Total sementara untuk billing yang masih aktif */
    public function getCurrentTotalAttribute(): float
    {
        // Gunakan grand_total final hanya saat billing sudah selesai.
        // Tidak bisa pakai (grand_total !== null) karena kolom default 0 di DB bukan null.
        if ($this->isCompleted()) {
            return (float) $this->grand_total;
        }

        $package = $this->package;
        // Fallback: jika billing tidak punya pricing_id langsung (mis. paket normal),
        // gunakan pricing dari package (untuk perhitungan extra jam / paket loss)
        $pricing = $this->pricing ?? $package?->pricing;

        // Minimum 1 jam — pembulatan ke bawah (floor) mengabaikan kelebihan menit
        $elapsedHours = max(1, (int) floor($this->elapsed_seconds / 3600));

        $base  = 0;
        $extra = 0;

        if (!$package) {
            // Tanpa paket: hitung dari harga/jam (actual elapsed)
            $base = $elapsedHours * (float)($pricing?->price_per_hour ?? 0);
        } elseif ($package->isNormal()) {
            // Paket normal: harga fix + extra jam
            $base = (float) $package->price;

            if ($this->isActive() && $this->scheduled_end_at) {
                // Billing aktif: gunakan jam yang direncanakan (scheduled) agar
                // perpanjangan waktu langsung terrefleksi di total tagihan
                $plannedHours = (int) $this->started_at->diffInHours($this->scheduled_end_at);
                $extraHrs     = max(0, $plannedHours - (int)$package->duration_hours);
            } else {
                // Billing selesai atau tanpa jadwal: pakai actual elapsed
                $extraHrs = max(0, $elapsedHours - (int)$package->duration_hours);
            }

            $extra = $extraHrs * (float)($pricing?->price_per_hour ?? 0);
        } else {
            // Paket loss: dihitung per jam dari awal (actual elapsed)
            $base = $elapsedHours * (float)($pricing?->price_per_hour ?? 0);
        }

        return round($base + $extra + (float)$this->addon_total - (float)$this->discount_amount, 2);
    }

    public function getFormattedCurrentTotalAttribute(): string
    {
        return 'Rp ' . number_format($this->current_total, 0, ',', '.');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }
}
