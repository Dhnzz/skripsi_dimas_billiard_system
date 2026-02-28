<?php

namespace App\Models;

use App\Models\Billing;
use App\Models\Booking;
use Illuminate\Database\Eloquent\Model;

class Table extends Model
{
    protected $fillable = [
        'table_number',
        'name',
        'description',
        'status',
        'is_active'
    ];

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function billing()
    {
        return $this->hasOne(Billing::class);
    }

    public function activeBilling()
    {
        return $this->hasOne(Billing::class)->where('status', 'active');
    }

     // ── Scopes ──────────────────────────────────────────────

    /** Booking yang sudah dikonfirmasi dan belum selesai (untuk cek antrian) */
    public function upcomingBookings()
    {
        return $this->hasMany(Booking::class)
            ->whereIn('status', ['confirmed', 'pending'])
            ->where('scheduled_date', '>=', today());
    }

    /** Cek apakah ada antrian booking setelah waktu tertentu */
    public function hasQueueAfter(\Carbon\Carbon $afterTime): bool
    {
        return $this->bookings()
            ->whereIn('status', ['confirmed', 'pending'])
            ->whereDate('scheduled_date', $afterTime->toDateString())
            ->whereTime('scheduled_start', '<', $afterTime->toTimeString())
            ->exists();
    }
}
