@extends('layouts.login_master')

@section('titulo', 'Nueva Contraseña | Sistema de Control de Asistencia')

@section('content')
    <div class="login-page">
        <div class="login-box">
            <div class="card">
                <div class="card-body login-card-body elevation-4">

                    <div class="text-center mb-4">
                        <h3 class="h4">Establecer nueva contraseña</h3>
                    </div>

                    <form method="POST" action="{{ route('password.update') }}">
                        @csrf

                        <input type="hidden" name="token" value="{{ $token }}">

                        <div class="input-group mb-3">
                            <div class="input-group-prepend">
                                <span class="input-group-text">
                                    <i class="fas fa-envelope"></i>
                                </span>
                            </div>
                            <input id="email" name="email" type="email"
                                class="form-control @error('email') is-invalid @enderror"
                                value="{{ $email ?? old('email') }}" required autocomplete="email" autofocus>
                            @error('email')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                        <div class="input-group mb-3">
                            <div class="input-group-prepend">
                                <span class="input-group-text">
                                    <i class="fas fa-lock"></i>
                                </span>
                            </div>
                            <input id="password" name="password" type="password"
                                class="form-control @error('password') is-invalid @enderror"
                                placeholder="Nueva contraseña" required autocomplete="new-password">
                            @error('password')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                        <div class="input-group mb-3">
                            <div class="input-group-prepend">
                                <span class="input-group-text">
                                    <i class="fas fa-lock"></i>
                                </span>
                            </div>
                            <input id="password-confirm" name="password_confirmation" type="password"
                                class="form-control" placeholder="Confirmar contraseña" required
                                autocomplete="new-password">
                        </div>

                        <button type="submit" class="btn btn-primary btn-block elevation-2">
                            Guardar nueva contraseña
                        </button>
                    </form>

                </div>
            </div>
        </div>
    </div>
@endsection