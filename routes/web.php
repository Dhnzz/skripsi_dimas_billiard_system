<?php

use App\Http\Controllers\LandingController;
// use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\RedirectController;

Route::get('/', [LandingController::class, 'index'])->name('landing');

Route::middleware(['auth', 'role:member'])->prefix('member')->name('member.')->group(function () {});

Route::middleware(['auth', 'role:kasir'])->prefix('kasir')->name('kasir.')->group(function () {});

Route::middleware(['auth', 'role:owner'])->prefix('owner')->name('owner.')->group(function () {});

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
