<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RedirectController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = Auth::user();
        if ($user->hasRole('owner')) return redirect()->route('owner.dashboard');
        if ($user->hasRole('kasir'))      return redirect()->route('kasir.dashboard');
        
        // Member tidak punya dashboard, langsung arahkan ke riwayat booking
        return redirect()->route('member.booking.index');
    }
}
