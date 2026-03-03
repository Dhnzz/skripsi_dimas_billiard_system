<?php

namespace App\Http\Controllers;


class DashboardController extends Controller
{
    /**
     * Halaman dashboard untuk role owner.
     */
    public function owner()
    {
        $breadcrumbs = [
            [
                'title' => 'Dashboard',
                'url' => route('owner.dashboard'),
            ],
            [
                'title' => 'Dashboard',
                'url' => null,
            ],
        ];

        return view('dashboard', [
            'title' => 'Dashboard',
            'breadcrumbs' => $breadcrumbs,
        ]);
    }
}
