<?php

namespace App\Models;

use App\Models\Package;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class Pricing extends Model
{
    protected $fillable = [
        'name',
        'price_per_hour',
        'apply_days',
        'start_time',
        'end_time',
        'is_active',
        'created_by'
    ];

    protected $casts = [
        'apply_days'     => 'array',
        'price_per_hour' => 'decimal:2',
        'is_active'      => 'boolean',
    ];

    protected static function booted()
    {
        static::saved(function ($pricing) {
            // Jika tarif ini di-set aktif, nonaktifkan semua tarif lainnya
            if ($pricing->is_active) {
                static::where('id', '!=', $pricing->id)->update(['is_active' => false]);
            }
        });
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function packages()
    {
        return $this->hasMany(Package::class);
    }

    public function isApplicableNow(): bool
    {
        $today = strtolower(now()->locale('id')->dayName);
        if ($this->apply_days && !in_array($today, $this->apply_days)) {
            return false;
        }
        if ($this->start_time && $this->end_time) {
            $currentTime = now()->format('H:i:s');
            return $currentTime >= $this->start_time && $currentTime <= $this->end_time;
        }
        return true;
    }

    public function getFormattedPriceAttribute(): string
    {
        return 'Rp ' . number_format((float) ($this->price_per_hour ?? 0), 0, ',', '.');
    }
}
