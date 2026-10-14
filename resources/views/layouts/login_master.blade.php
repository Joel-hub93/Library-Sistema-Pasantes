<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('titulo', 'Sistema de Control de Asistencia')</title>

    <!-- Google Font: Source Sans Pro (mismo que layouts.admin) -->
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="{{ asset('plugins/fontawesome-free/css/all.min.css') }}">
    <!-- Theme style -->
    <link rel="stylesheet" href="{{ asset('dist/css/adminlte.min.css') }}">
    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="{{ asset('plugins/sweetalert2/sweetalert2.min.css') }}">
    <script src="{{ asset('plugins/sweetalert2/sweetalert2.min.js') }}"></script>
    <!-- Avisos toast: en el head porque los toasts se disparan al inicio del body -->
    <script src="{{ asset('js/avisos.js') }}"></script>

    <style>
        body.login-page {
            background-color: #0f2a4a;
            /* color base por si la foto no carga */
            background-image:
                linear-gradient(135deg, rgba(10, 40, 80, .50), rgba(6, 20, 42, .66)),
                radial-gradient(ellipse at center, rgba(0, 0, 0, 0) 45%, rgba(0, 0, 0, .38) 100%),
                url('{{ asset('img/principal.jpg') }}');
            background-size: cover, cover, cover;
            background-position: center center;
            background-repeat: no-repeat;
            min-height: 100vh;
            height: auto;
        }


        .wrapper .login-page {
            background: none;
            height: auto;
            min-height: 0;
        }

        .wrapper {
            padding: 1.5rem 1rem;
        }


        .login-box {
            width: 430px;
            max-width: 94vw;
        }

        /* Entrada suave de la tarjeta: suaviza el cambio entre el login de
           admin y el de pasantes (mismo layout base para todos los accesos). */
        .login-box {
            animation: loginFadeIn .20s ease-out;
        }

        @keyframes loginFadeIn {
            from {
                opacity: 0;
                transform: translateY(14px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .login-box .card {
            border-radius: .75rem;
            overflow: hidden;

        }


        .login-card-body {
            padding: 2rem;
            color: #444;
        }


        .login-card-body .input-group .form-control,
        .login-card-body .input-group .input-group-text {
            font-size: 1rem;
            padding: .5rem 1rem;
        }

        .login-card-body .input-group .form-control {
            height: calc(2.875rem + 2px);
        }


        .login-card-body .input-group .form-control:focus {
            background-color: #fff;
            box-shadow: 0 0 0 .2rem rgba(0, 123, 255, .2);
        }


        .login-logo {
            width: 84px;
            height: 84px;
            border-radius: 30%;
            object-fit: cover;
        }

        .login-box .card-footer {
            font-size: .85rem;
            color: #6c757d;
        }


        .login-box .card-footer.bg-white {
            padding: 1rem .5rem !important;
            background-color: #fff;
        }


        @media (max-width: 576px) {
            .login-card-body {
                padding: 1.25rem;
            }

            .login-logo {
                width: 64px;
                height: 64px;
            }
        }

        @media (max-height: 560px) {
            body.login-page {
                justify-content: flex-start;
            }

            .login-card-body {
                padding: 1rem 1.25rem;
            }

            .login-logo {
                display: none;
            }
        }

        @media (prefers-reduced-motion: reduce) {

            .btn-lg,
            .modal.fade .modal-dialog {
                transition: none;
            }

            .login-box {
                animation: none;
            }
        }

        /* Overlay de carga (widget .preloader de AdminLTE): fondo del color
           institucional para que el paso entre pantallas de acceso no haga
           "flash" blanco. */
        .preloader {
            background-color: #0f2a4a;
        }

        .preloader img {
            border-radius: .5rem;
        }
    </style>

    @stack('estilos')

</head>

<body class="hold-transition login-page">

    {{-- Overlay de carga de AdminLTE: cubre la pantalla mientras se cambia de
         pagina (login de admin <-> login de pasante) para que el paso no se vea
         brusco. Al terminar de cargar se encoge y revela el formulario. --}}
    <div id="preloader" class="preloader flex-column justify-content-center align-items-center">
        <img src="{{ asset('img/Logo.jpg') }}" class="animation__shake" height="80" width="80"
            alt="Logo Sistema de Control de Asistencia">
        <p class="mt-2 text-white"><small>Cargando el acceso...</small></p>
    </div>

    <div class="wrapper">
        @yield('content')
    </div>
    <!-- ./wrapper -->

    <!-- REQUIRED SCRIPTS -->

    <!-- jQuery -->
    <script src="{{ asset('plugins/jquery/jquery.min.js') }}"></script>
    <!-- Bootstrap 4 -->
    <script src="{{ asset('plugins/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <!-- AdminLTE App -->
    <script src="{{ asset('dist/js/adminlte.min.js') }}"></script>

    <script>
        // Overlay de carga (widget .preloader de AdminLTE). Al terminar de cargar
        // la pagina se encoge y deja ver el formulario; al navegar entre el login
        // de admin y el de pasantes se despliega ANTES del cambio de pagina para
        // que el paso no se vea de golpe.
        $(function () {
            var $pre = $('#preloader');
            var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

            function encoger() {
                $pre.animate({ height: 0 }, reduceMotion ? 0 : 300, function () {
                    $pre.hide();
                });
            }

            function desplegar() {
                $pre.css({ height: '100%', display: 'flex', backgroundColor: '#0f2a4a' });
                $pre.children().show();
            }

            if (document.readyState === 'complete') {
                encoger();
            } else {
                $(window).on('load', encoger);
                setTimeout(encoger, 1000); // respaldo por si el evento 'load' se demora
            }

            // Enlaces marcados con data-loader: muestran el overlay antes de navegar.
            $(document).on('click', '[data-loader]', function (e) {
                if (reduceMotion) {
                    return; // navegacion normal, sin animaciones
                }

                e.preventDefault();
                desplegar();
                location.href = $(this).attr('href');
            });
        });
    </script>

    @stack('scripts')

</body>

</html>
