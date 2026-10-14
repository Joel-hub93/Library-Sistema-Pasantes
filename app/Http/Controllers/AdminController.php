<?php

namespace App\Http\Controllers;

use App\Models\Carrera;
use App\Models\Pasante;
use App\Models\Tutor;
use App\Models\User;
use Illuminate\Routing\Attributes\Controllers\Middleware;

#[Middleware('auth')]
class AdminController extends Controller
{
    /**
     * Show the admin dashboard with module counters.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        return view('index', [
            'totalPasantes' => Pasante::count(),
            'totalCarreras' => Carrera::count(),
            'totalTutores'  => Tutor::count(),
            'totalUsuarios' => User::count(),
            'fechaHoy'      => now()->locale('es')->isoFormat('dddd, D [de] MMMM [de] YYYY'),
        ]);
    }
}