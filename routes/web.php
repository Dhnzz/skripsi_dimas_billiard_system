<?php

use App\Http\Controllers\Auth\RedirectController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LandingController;
use Illuminate\Support\Facades\Route;

Route::get('/', [LandingController::class, 'index'])->name('landing');

Route::middleware(['auth', 'role:member'])->prefix('member')->name('member.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'owner'])->name('dashboard');
});

Route::middleware(['auth', 'role:kasir'])->prefix('kasir')->name('kasir.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'owner'])->name('dashboard');
});

Route::middleware(['auth', 'role:owner'])->prefix('owner')->name('owner.')->group(function () {
    // Route::get('/dashboard', [DashboardController::class, 'owner'])->name('dashboard');
    Route::livewire('/dashboard', 'dashboard')->name('dashboard');
    Route::livewire('/kasir', 'pages.kasir.index')->name('kasir.index');
    Route::livewire('/kasir/create', 'pages.kasir.create')->name('kasir.create');
    Route::livewire('/kasir/{id}/edit', 'pages.kasir.edit')->name('kasir.edit');
    Route::livewire('/member', 'pages::member.index')->name('member.index');
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
