<?php

namespace App\Http\Controllers;

use App\Models\Package;
use App\Models\Table;
use Illuminate\View\View;


class LandingController extends Controller
{
    public function index(): View
    {
        // Ambil paket aktif untuk ditampilkan di section Paket
        $packages = Package::where('is_active', true)
            ->with('pricing')      // Eager load pricing untuk paket loss
            ->orderByRaw("FIELD(type, 'normal', 'loss')")  // Normal dulu, baru loss
            ->orderBy('price')     // Urutkan dari harga termurah
            ->get();

        // Statistik meja untuk Stats Bar
        $totalMeja     = Table::where('is_active', true)->count();
        $mejaAvailable = Table::where('is_active', true)->where('status', 'available')->count();
        $mejaOccupied  = Table::where('is_active', true)->where('status', 'occupied')->count();

        return view('welcome', compact(
            'packages',
            'totalMeja',
            'mejaAvailable',
            'mejaOccupied',
        ));
    }
}
