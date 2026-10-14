@extends('layouts.login_master')

@section('titulo', 'Confirmar Contraseña | Sistema de Control de Asistencia')

@section('content')
    <div class="login-page">
        <div class="login-box">
            <div class="card">
                <div class="card-body login-card-body elevation-4">

                    <div class="text-center mb-4">
                        <h3 class="h4">Confirmar contraseña</h3>
                        <p class="text-muted mb-0">
                            Por favor confirma tu contraseña antes de continuar.
                        </p>
                    </div>

                    <form method="POST" action="{{ route('password.confirm') }}">
                        @csrf

                        <div class="input-group mb-3">
                            <div class="input-group-prepend">
                                <span class="input-group-text">
                                    <i class="fas fa-lock"></i>
                                </span>
                            </div>
                            <input id="password" name="password" type="password"
                                class="form-control @error('password') is-invalid @enderror"
                                placeholder="Contraseña" required autocomplete="current-password" autofocus>
                            @error('password')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                        <button type="submit" class="btn btn-primary btn-block elevation-2">
                            Confirmar contraseña
                        </button>
                    </form>

                    <div class="mt-3 text-center">
                        @if (Route::has('password.request'))
                            <a href="{{ route('password.request') }}" class="btn btn-link p-0 m-0">
                                ¿Olvidaste tu contraseña?
                            </a>
                        @endif
                    </div>

                </div>
            </div>
        </div>
    </div>
@endsection