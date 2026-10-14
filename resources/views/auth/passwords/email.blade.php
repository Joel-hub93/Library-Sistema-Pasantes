@extends('layouts.login_master')

@section('titulo', 'Restablecer Contraseña | Sistema de Control de Asistencia')

@section('content')
    <div class="login-page">
        <div class="login-box">
            <div class="card">
                <div class="card-body login-card-body elevation-4">

                    <div class="text-center mb-4">
                        <h3 class="h4">Restablecer contraseña</h3>
                        <p class="text-muted mb-0">
                            Ingresa tu correo y te enviaremos un enlace para restablecerla.
                        </p>
                    </div>

                    @if (session('status'))
                        @include('partials.toast', [
                            'icono' => 'success',
                            'titulo' => '¡Éxito!',
                            'mensaje' => session('status'),
                        ])
                    @endif

                    <form method="POST" action="{{ route('password.email') }}">
                        @csrf

                        <div class="input-group mb-3">
                            <div class="input-group-prepend">
                                <span class="input-group-text">
                                    <i class="fas fa-envelope"></i>
                                </span>
                            </div>
                            <input id="email" name="email" type="email"
                                class="form-control @error('email') is-invalid @enderror"
                                placeholder="correo@istvidanueva.edu.ec" value="{{ old('email') }}"
                                required autocomplete="email" autofocus>
                            @error('email')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                        <button type="submit" class="btn btn-primary btn-block elevation-2">
                            Enviar enlace de restablecimiento
                        </button>
                    </form>

                    <div class="mt-3 text-center">
                        <a href="{{ route('login') }}" class="btn btn-link p-0 m-0">Volver al inicio de sesión</a>
                    </div>

                </div>
            </div>
        </div>
    </div>
@endsection