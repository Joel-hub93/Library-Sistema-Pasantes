@extends('layouts.login_master')

@section('titulo', 'Iniciar Sesión | Sistema de Control de Asistencia')

@section('content')
    <div class="login-page">
        <div class="login-box">
            <div class="card">

                <div class="card-body login-card-body elevation-4">
                    <div class="text-center mb-4">
                        <img src="{{ asset('img/Logo.jpg') }}" alt="Logo Sistema de Control de Asistencia"
                            class="login-logo mb-3" width="70" height="70">
                        <h3 class="h4">Sistema de Control de Asistencia</h3>
                        <p class="text-muted mb-0">Tecnológico Universitario Vida Nueva</p>
                    </div>

                    {{-- Sin esto el mensaje se perdia: la sesion lo traia pero esta
                         pantalla no lo mostraba y el pasante veia un login limpio --}}
                    @if (session('error'))
                        @include('partials.toast', [
                            'icono' => 'error',
                            'titulo' => 'Error',
                            'mensaje' => session('error'),
                        ])
                    @endif

                    @if (session('confirmacion'))
                        @include('partials.toast', [
                            'icono' => 'success',
                            'titulo' => '¡Éxito!',
                            'mensaje' => session('confirmacion'),
                        ])
                    @endif

                    <form method="POST" action="{{ route('login') }}">
                        @csrf

                        <div class="input-group mb-3">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                            </div>
                            <input id="email" name="email" type="email"
                                class="form-control @error('email') is-invalid @enderror"
                                placeholder="correo@istvidanueva.edu.ec" value="{{ old('email') }}" required
                                autocomplete="email" autofocus>
                            @error('email')
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
                                class="form-control @error('password') is-invalid @enderror" placeholder="Contraseña"
                                required autocomplete="current-password">
                            @error('password')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
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
                            @if (Route::has('password.request'))
                                <a href="{{ route('password.request') }}">
                                    ¿Olvidaste tu contraseña?
                                </a>
                            @endif
                        </div>
                    </form>
                </div>
                <!-- /.login-card-body -->

                {{-- Acceso de pasantes: cuenta propia, cedula + contrasena --}}
                <div class="card-footer bg-light border-top text-center py-3">

                    <a href="{{ route('pasantes.login') }}" data-loader class="btn btn-outline-primary btn-block">
                        <i class="fas fa-id-card mr-2"></i> Acceso para pasantes
                    </a>
                </div>

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
