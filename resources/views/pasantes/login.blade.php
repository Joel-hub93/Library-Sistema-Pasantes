@extends('layouts.login_master')

@section('titulo', 'Acceso para pasantes | Sistema de Control de Asistencia')

@section('content')
    <div class="login-page">
        <div class="login-box">
            <div class="card">

                <div class="card-body login-card-body elevation-4">
                    <div class="text-center mb-4">
                        <img src="{{ asset('img/Logo.jpg') }}" alt="Logo Sistema de Control de Asistencia"
                            class="login-logo mb-3" width="70" height="70">
                        <h3 class="h4">Acceso para pasantes</h3>
                        <p class="text-muted mb-0">Ingresa con tu cédula</p>
                    </div>

                    @if (session('error'))
                        @include('partials.toast', [
                            'icono' => 'error',
                            'titulo' => 'Error',
                            'mensaje' => session('error'),
                        ])
                    @endif

                    <form method="POST" action="{{ route('pasantes.login.enviar') }}">
                        @csrf

                        <div class="input-group mb-3">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-id-card"></i></span>
                            </div>
                            <input id="cedula" name="cedula" type="text"
                                class="form-control @error('cedula') is-invalid @enderror"
                                placeholder="Número de cédula" value="{{ old('cedula') }}" maxlength="10"
                                inputmode="numeric" required autofocus autocomplete="username">
                            @error('cedula')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                        <div class="input-group mb-3">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                            </div>
                            <input id="password" name="password" type="password"
                                class="form-control @error('password') is-invalid @enderror"
                                placeholder="Contraseña" required autocomplete="current-password">
                            @error('password')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                        <div class="alert alert-info small mb-3">
                            <i class="fas fa-info-circle mr-1"></i>
                            Tu contraseña es tu mismo número de cédula.
                        </div>

                        <button type="submit" class="btn btn-primary btn-lg btn-block elevation-2">
                            Ingresar
                        </button>

                        <div class="custom-control custom-checkbox text-center mt-3 mb-1">
                            <input type="checkbox" class="custom-control-input" id="remember" name="remember"
                                {{ old('remember') ? 'checked' : '' }}>
                            <label class="custom-control-label" for="remember">Recordarme</label>
                        </div>

                        <div class="text-center">
                            <a href="{{ route('login') }}" data-loader>
                                <i class="fas fa-user-shield mr-1"></i> ¿Eres administrador?
                            </a>
                        </div>
                    </form>
                </div>
                <!-- /.login-card-body -->

                <div class="card-footer bg-white text-center p-0">
                    <strong>
                        <a href="https://vidanueva.edu.ec/">Tecnológico Universitario Vida Nueva</a>
                    </strong>
                    <br>
                    <strong>
                        Copyright &copy; 2026
                    </strong>
                </div>

            </div>
            <!-- /.card -->
        </div>
        <!-- /.login-box -->
    </div>
    <!-- /.login-page -->
@endsection