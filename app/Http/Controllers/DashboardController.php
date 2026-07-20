<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    /**
     * Menampilkan halaman utama
     * Global Supply Chain Intelligence Platform.
     */
    public function index(): View
    {
        return view('dashboard');
    }
}