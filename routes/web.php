<?php

use App\Http\Controllers\Auth\RedirectController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LandingController;
use Illuminate\Support\Facades\Route;

Route::get('/', [LandingController::class, 'index'])->name('landing');

Route::middleware(['auth', 'role:member'])->prefix('member')->name('member.')->group(function () {
    // Tidak ada dashboard untuk member, langsung arahkan ke booking
    // Booking Member
    Route::livewire('/booking', 'pages.member-booking.index')->name('booking.index');
    Route::livewire('/booking/create', 'pages.member-booking.create')->name('booking.create');
    Route::livewire('/booking/{id}', 'pages.member-booking.show')->name('booking.show');
});

Route::middleware(['auth', 'role:kasir'])->prefix('kasir')->name('kasir.')->group(function () {
    Route::livewire('/dashboard', 'pages.kasir.dashboard')->name('dashboard');


    // ── Operasional Utama ─────────────────────────────────────────
    // Booking
    Route::livewire('/booking', 'pages.booking.index')->name('booking.index');
    Route::livewire('/booking/{id}', 'pages.booking.show')->name('booking.show');
    // Billing
    Route::livewire('/billing', 'pages.billing.index')->name('billing.index');
    Route::livewire('/billing/create', 'pages.billing.create')->name('billing.create');
    Route::livewire('/billing/{id}', 'pages.billing.show')->name('billing.show');

    // ── Data Referensi (read-only) ────────────────────────────────
    // Status & Lihat Meja
    Route::livewire('/meja', 'pages.meja.index')->name('meja.index');
    Route::livewire('/meja/{id}', 'pages.meja.show')->name('meja.show');
    // Daftar Member (referensi nama, kontak)
    Route::livewire('/member', 'pages.member.index')->name('member.index');
    // Daftar Addon (referensi harga)
    Route::livewire('/addon', 'pages.addon.index')->name('addon.index');
    // Tarif & Paket (referensi harga)
    Route::livewire('/tarif', 'pages.pricing.index')->name('pricing.index');
    Route::livewire('/paket', 'pages.package.index')->name('package.index');
});


Route::middleware(['auth', 'role:owner'])->prefix('owner')->name('owner.')->group(function () {
    // Route::get('/dashboard', [DashboardController::class, 'owner'])->name('dashboard');
    Route::livewire('/dashboard', 'dashboard')->name('dashboard');
    // Manajemen Kasir
    Route::livewire('/kasir', 'pages.kasir.index')->name('kasir.index');
    Route::livewire('/kasir/create', 'pages.kasir.create')->name('kasir.create');
    Route::livewire('/kasir/{id}/edit', 'pages.kasir.edit')->name('kasir.edit');
    // Manajemen Member
    Route::livewire('/member', 'pages.member.index')->name('member.index');
    Route::livewire('/member/create', 'pages.member.create')->name('member.create');
    Route::livewire('/member/{id}/edit', 'pages.member.edit')->name('member.edit');
    // Manajemen Meja
    Route::livewire('/meja', 'pages.meja.index')->name('meja.index');
    Route::livewire('/meja/create', 'pages.meja.create')->name('meja.create');
    Route::livewire('/meja/{id}/edit', 'pages.meja.edit')->name('meja.edit');
    Route::livewire('/meja/{id}', 'pages.meja.show')->name('meja.show');
    // Manajemen Addon
    Route::livewire('/addon', 'pages.addon.index')->name('addon.index');
    Route::livewire('/addon/create', 'pages.addon.create')->name('addon.create');
    Route::livewire('/addon/{id}/edit', 'pages.addon.edit')->name('addon.edit');
    // Manajemen Tarif
    Route::livewire('/pricing', 'pages.pricing.index')->name('pricing.index');
    Route::livewire('/pricing/create', 'pages.pricing.create')->name('pricing.create');
    Route::livewire('/pricing/{id}/edit', 'pages.pricing.edit')->name('pricing.edit');
    // Manajemen Paket
    Route::livewire('/package', 'pages.package.index')->name('package.index');
    Route::livewire('/package/create', 'pages.package.create')->name('package.create');
    Route::livewire('/package/{id}/edit', 'pages.package.edit')->name('package.edit');
    // Monitoring Booking & Billing
    Route::livewire('/booking', 'pages.booking.index')->name('booking.index');
    Route::livewire('/booking/{id}', 'pages.booking.show')->name('booking.show');
    Route::livewire('/billing', 'pages.billing.index')->name('billing.index');
    Route::livewire('/billing/create', 'pages.billing.create')->name('billing.create');
    Route::livewire('/billing/{id}', 'pages.billing.show')->name('billing.show');
});

// Route::get('/dashboard', function () {
//     return view('dashboard');
// })->middleware(['auth', 'verified'])->name('dashboard');

// Route::middleware('auth')->group(function () {
//     Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
//     Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
//     Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
// });

require __DIR__ . '/auth.php';
Route::get('/redirect', RedirectController::class)
    ->middleware('auth')->name('redirect');
