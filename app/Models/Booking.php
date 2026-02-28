<?php

namespace App\Models;

use App\Models\Billing;
use App\Models\Package;
use App\Models\Pricing;
use App\Models\Table;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    protected $fillable = [
        'booking_code',
        'customer_id',
        'table_id',
        'package_id',
        'pricing_id',
        'scheduled_date',
        'scheduled_start',
        'scheduled_end',
        'notes',
        'status',
        'confirmed_by',
        'confirmed_at',
        'rejected_reason'
    ];

    protected $casts = [
        'scheduled_date' => 'date',
        'confirmed_at'   => 'datetime',
    ];

    // GENERATE BOOKING CODE
    // ── Boot: Auto-generate booking_code ────────────────────

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($booking) {
            if (empty($booking->booking_code)) {
                $booking->booking_code = self::generateCode();
            }
        });
    }

    public static function generateCode(): string
    {
        $date   = now()->format('Ymd');
        $latest = self::whereDate('created_at', today())->count() + 1;
        return 'BK-' . $date . '-' . str_pad($latest, 3, '0', STR_PAD_LEFT);
    }

    // RELATIONS
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

    public function confirmedBy()
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    public function billing()
    {
        return $this->hasOne(Billing::class);
    }

    // ── Helpers ─────────────────────────────────────────────

    public function isPending(): bool    { return $this->status === 'pending'; }
    public function isConfirmed(): bool  { return $this->status === 'confirmed'; }
    public function isRejected(): bool   { return $this->status === 'rejected'; }
    public function isCancelled(): bool  { return $this->status === 'cancelled'; }
    public function isCompleted(): bool  { return $this->status === 'completed'; }

    public function getStatusBadgeColorAttribute(): string
    {
        return match($this->status) {
            'pending'   => 'yellow',
            'confirmed' => 'green',
            'rejected'  => 'red',
            'cancelled' => 'gray',
            'completed' => 'blue',
            default     => 'gray',
        };
    }
}
