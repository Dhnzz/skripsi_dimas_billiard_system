<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Billing;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Pricing;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'is_active',
        'avatar'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function customerBookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function confirmBookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function customerBillings()
    {
        return $this->hasMany(Billing::class);
    }

    public function startBillings()
    {
        return $this->hasMany(Billing::class);
    }

    public function endBillings()
    {
        return $this->hasMany(Billing::class);
    }

    public function createPricings()
    {
        return $this->hasMany(Pricing::class);
    }

    public function customerPayments()
    {
        return $this->hasMany(Payment::class);
    }

    public function processPayments()
    {
        return $this->hasMany(Payment::class);
    }
}
