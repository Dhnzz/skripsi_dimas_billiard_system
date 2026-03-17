<?php

namespace App\Models;

use App\Models\Addon;
use App\Models\Billing;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\Pivot;

class BillingAddon extends Pivot
{
    protected $table = 'billing_addons';

    protected $fillable = [
        'billing_id',
        'addon_id',
        'quantity',
        'unit_price',
        'subtotal',
        'status',
        'requested_by',
        'requested_by_role',
        'confirmed_by',
        'confirmed_at',
        'notes'
    ];

    protected $casts = [
        'unit_price'   => 'decimal:2',
        'subtotal'     => 'decimal:2',
        'confirmed_at' => 'datetime',
    ];

    public function billing()  { return $this->belongsTo(Billing::class); }
    public function addon()    { return $this->belongsTo(Addon::class); }

    public function requestedByUser()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function confirmedByUser()
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    public function isPending(): bool    { return $this->status === 'pending'; }
    public function isConfirmed(): bool  { return $this->status === 'confirmed'; }
    public function isCancelled(): bool  { return $this->status === 'cancelled'; }

    public function getFormattedSubtotalAttribute(): string
    {
        return 'Rp ' . number_format((float) $this->subtotal, 0, ',', '.');
    }
}
