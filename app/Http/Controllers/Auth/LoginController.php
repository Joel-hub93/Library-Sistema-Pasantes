<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Attributes\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;

#[Middleware('guest:web', except: ['logout'])]
#[Middleware('auth', only: ['logout'])]
class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = '/';

    /**
     * Restrict login to active users only (estado = 1).
     */
    protected function credentials(Request $request): array
    {
        return [
            'email' => $request->get('email'),
            'password' => $request->get('password'),
            'estado' => '1',
        ];
    }

    /**
     * El login de administradores usa SIEMPRE la guard 'web'.
     *
     * No se confia en la guard por defecto: el pasante puede cambiar la guard
     * activa con su propia sesion, y un login de admin hecha contra la guard
     * 'pasante' buscaria el correo en la tabla equivocada.
     */
    protected function guard()
    {
        return Auth::guard('web');
    }

    /**
     * Respuesta tras un login de administrador.
     *
     * Si el 'url.intended' lo dejo el middleware de la guard 'pasante'
     * (al rebotar desde /asistencias/registrar y similares), es una ruta que
     * las cuentas 'web' no pueden abrir: se descarta para no encadenar el
     * login con otro rebote.
     */
    protected function sendLoginResponse(Request $request): RedirectResponse
    {
        $request->session()->regenerate();

        $this->clearLoginAttempts($request);

        $intended = $request->session()->get('url.intended');

        if ($intended && str_contains((string) parse_url($intended, PHP_URL_PATH), '/asistencias/')) {
            $request->session()->forget('url.intended');
        }

        return $this->authenticated($request, $this->guard()->user())
            ?: redirect()->intended($this->redirectPath());
    }
}
