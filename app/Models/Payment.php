<?php

namespace App\Models;

use App\Models\Billing;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
        'payment_code',
        'billing_id',
        'customer_id',
        'amount',
        'amount_paid',
        'change_amount',
        'method',
        'status',
        'midtrans_order_id',
        'midtrans_transaction_id',
        'midtrans_response',
        'paid_at',
        'processed_by'
    ];

    protected $casts = [
        'amount'             => 'decimal:2',
        'amount_paid'        => 'decimal:2',
        'change_amount'      => 'decimal:2',
        'midtrans_response'  => 'array',
        'paid_at'            => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($payment) {
            if (empty($payment->payment_code)) {
                $date   = now()->format('Ymd');
                $latest = self::whereDate('created_at', today())->count() + 1;
                $payment->payment_code = 'PAY-' . $date . '-' . str_pad($latest, 3, '0', STR_PAD_LEFT);
            }
        });
    }

    public function billing()
    {
        return $this->belongsTo(Billing::class);
    }

    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function processedBy()
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function getFormattedAmountAttribute(): string
    {
        return 'Rp ' . number_format((float) $this->amount, 0, ',', '.');
    }
}
