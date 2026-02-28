<?php

namespace App\Models;

use App\Models\Billing;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class BillingTimeExtension extends Model
{
    protected $fillable = [
        'billing_id',
        'added_hours',
        'price_per_hour',
        'total_price',
        'extended_by',
        'new_scheduled_at'
    ];

    protected $casts = [
        'new_scheduled_end_at' => 'datetime',
        'added_hours'          => 'decimal:2',
        'price_per_hour'       => 'decimal:2',
        'total_price'          => 'decimal:2',
    ];

    public function billing()
    {
        return $this->belongsTo(Billing::class);
    }

    public function extendedByUser()
    {
        return $this->belongsTo(User::class, 'extended_by');
    }
}
