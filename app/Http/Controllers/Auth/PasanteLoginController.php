<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Login de pasantes.
 *
 * El pasante entra con su cedula como usuario y su misma cedula como
 * contrasena (cifrada). Usa la guard 'pasante', independiente de la guard
 * 'web', así ninguna identidad se mezcla con la del administrador.
 */
class PasanteLoginController extends Controller
{
    public function showLoginForm(): View|RedirectResponse
    {
        if (Auth::guard('pasante')->check()) {
            return redirect()->route('asistencias.registrar');
        }

        return view('pasantes.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'cedula' => ['required', 'string', 'max:10'],
            'password' => ['required', 'string'],
        ], [
            'cedula.required' => 'Ingresa tu número de cédula.',
            'password.required' => 'Ingresa tu contraseña (tu número de cédula).',
        ]);

        $credenciales = [
            'cedula' => $datos['cedula'],
            'password' => $datos['password'],
            'estado' => '1',
        ];

        if (Auth::guard('pasante')->attempt($credenciales, $request->boolean('remember'))) {
            $request->session()->regenerate();

            // No usar redirect()->intended(): ese apuntador lo dejan los
            // middlewares 'auth' (guard web) al rebotar al visitante desde una
            // pagina de administrador, y manda al pasante a una ruta que su guard
            // no puede abrir (por ejemplo la portada "/"), que lo bota al login
            // de admin. El pasante SIEMPRE aterriza en su modulo de marcado.
            $request->session()->forget('url.intended');

            return redirect()->route('asistencias.registrar');
        }

        return back()
            ->withErrors(['cedula' => 'Cédula o contraseña incorrectos, o tu cuenta está inactiva.'])
            ->onlyInput('cedula');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('pasante')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
