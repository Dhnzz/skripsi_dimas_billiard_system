<?php

namespace App\Models;

use App\Models\Pricing;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class Package extends Model
{
    protected $fillable = [
        'name',
        'type',
        'duration_hours',
        'price',
        'pricing_id',
        'description',
        'is_active',
        'created_by'
    ];

    protected $casts = [
        'duration_hours' => 'decimal:2',
        'price'          => 'decimal:2',
        'is_active'      => 'boolean',
    ];

    public function pricing()
    {
        return $this->belongsTo(Pricing::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isNormal(): bool { return $this->type === 'normal'; }
    public function isLoss(): bool   { return $this->type === 'loss'; }

    public function getFormattedPriceAttribute(): string
    {
        if ($this->isNormal()) {
            return 'Rp ' . number_format((float) $this->price, 0, ',', '.');
        }
        return $this->pricing
            ? 'Rp ' . number_format((float) $this->pricing->price_per_hour, 0, ',', '.') . '/jam'
            : 'Harga belum diset';
    }

}
