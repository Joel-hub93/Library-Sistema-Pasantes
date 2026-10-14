@php
    // El pasante autenticado con su cuenta propia comparte este mismo layout.
    //
    // Importante: la guard 'pasante' es INDEPENDIENTE de la guard 'web'. Si el
    // mismo navegador entra como pasante y luego como administrador, no se
    // mezclan: el layout dibuja el menu de admin (guard web) y deja fuera el del
    // pasante, y viceversa.
    $pasanteSesion = Auth::guard('pasante')->user();
    $esPasante = Auth::guard('pasante')->check() && Auth::guard('web')->guest();
    $portada = $esPasante ? route('asistencias.registrar') : route('inicio');
@endphp
<!DOCTYPE html>
<!-- AdminLTE 3: es el unico layout del sistema, lo usan el panel y el acceso de pasantes. -->
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sistema de Control de Asistencia</title>

    <!-- Google Font: Source Sans Pro -->
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="{{ asset('plugins/fontawesome-free/css/all.min.css') }}">
    <!-- Theme style -->
    <link rel="stylesheet" href="{{ asset('dist/css/adminlte.min.css') }}">
    <!-- jQuery -->
    <script src="{{ asset('plugins/jquery/jquery.min.js') }}"></script>
    <!-- DataTables -->
    <link rel="stylesheet" href="{{ asset('plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('plugins/datatables-responsive/css/responsive.bootstrap4.min.css') }}">
    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="{{ asset('plugins/sweetalert2/sweetalert2.min.css') }}">
    <script src="{{ asset('plugins/sweetalert2/sweetalert2.min.js') }}"></script>
    <!-- Avisos toast: en el head porque los toasts se disparan al inicio del body -->
    <script src="{{ asset('js/avisos.js') }}"></script>
    @stack('estilos')

</head>

<body class="hold-transition sidebar-mini">
    <div class="wrapper">

        <!-- Navbar -->
        <nav class="main-header navbar navbar-expand navbar-white navbar-light">
            <!-- Left navbar links -->
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i
                            class="fas fa-bars"></i></a>
                </li>
                <li class="nav-item d-none d-sm-inline-block">
                    <a href="{{ $portada }}"
                        class="nav-link">Sistema de Control de Asistencia</a>
                </li>
            </ul>

            <!-- Right navbar links -->
            <ul class="navbar-nav ml-auto">
                {{-- Botón de salida con el rol de quien está logueado --}}
                @if ($esPasante)
                    <li class="nav-item">
                        <form action="{{ route('pasantes.logout') }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="btn nav-link" title="Cerrar sesión">
                                <i class="fas fa-sign-out-alt mr-1"></i> Pasante
                            </button>
                        </form>
                    </li>
                @elseif (Auth::check())
                    <li class="nav-item">
                        <form action="{{ route('logout') }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="btn nav-link" title="Cerrar sesión">
                                <i class="fas fa-sign-out-alt mr-1"></i> Admin
                            </button>
                        </form>
                    </li>
                @else
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('login') }}">Login</a>
                    </li>
                @endif

                @auth
                {{-- Pantalla completa: util para revisar tablas largas. --}}
                <li class="nav-item">
                    <a class="nav-link" data-widget="fullscreen" href="#" role="button"
                        title="Pantalla completa">
                        <i class="fas fa-expand-arrows-alt"></i>
                    </a>
                </li>
                @endauth
            </ul>
        </nav>
        <!-- /.navbar -->

        <!-- Main Sidebar Container -->
        <aside class="main-sidebar sidebar-dark-primary elevation-4">
            <!-- Brand Logo -->
            <a href="{{ $portada }}"
                class="brand-link">
                <img src="{{ asset('img/Logo.jpg') }}" alt="Logo-Principal"
                    class="brand-image img-circle elevation-4" style="opacity: .8">
                <span class="brand-text font-weight-light">Library-TUVN</span>
            </a>

            <!-- Sidebar -->
            <div class="sidebar">
                <!-- Sidebar user panel (optional) -->
                <div class="user-panel mt-3 pb-3 mb-3 d-flex">
                    <div class="info">
                        @if ($esPasante)
                            <a href="{{ route('asistencias.registrar') }}">{{ $pasanteSesion->apellidos }} {{ $pasanteSesion->nombres }}</a>
                        @else
                            <a href="#">{{ Auth::user()->name ?? 'Usuario' }}</a>
                        @endif
                    </div>
                </div>

                <!-- Sidebar Menu -->
                <nav class="mt-2">
                    <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu"
                        data-accordion="false">
                        <!-- Add icons to the links using the .nav-icon class
               with font-awesome or any other icon font library -->

                        {{-- Menú del pasante: solo lo que le corresponde a él --}}
                        @if ($esPasante)
                            <li class="nav-item {{ request()->routeIs('asistencias.*') ? 'menu-open' : '' }}"
                                data-menu="asistencia">
                                <a href="#"
                                    class="nav-link {{ request()->routeIs('asistencias.*') ? 'active' : '' }}">
                                    <i class="nav-icon fas fa-clipboard-check"></i>
                                    <p>
                                        Asistencia
                                        <i class="right fas fa-angle-left"></i>
                                    </p>
                                </a>
                                <ul class="nav nav-treeview">
                                    <li class="nav-item">
                                        <a href="{{ route('asistencias.registrar') }}"
                                            class="nav-link {{ request()->routeIs('asistencias.registrar') ? 'active' : '' }}">
                                            <i class="nav-icon fas fa-camera-retro"></i>
                                            <p>Registrar</p>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="{{ route('asistencias.registros') }}"
                                            class="nav-link {{ request()->routeIs('asistencias.registros') ? 'active' : '' }}">
                                            <i class="nav-icon fas fa-calendar-day"></i>
                                            <p>Registro actual</p>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="{{ route('asistencias.historial') }}"
                                            class="nav-link {{ request()->routeIs('asistencias.historial') ? 'active' : '' }}">
                                            <i class="nav-icon fas fa-history"></i>
                                            <p>Historial</p>
                                        </a>
                                    </li>
                                </ul>
                            </li>

                            {{-- Reportes propios del pasante: bitacora y informe final --}}
                            <li class="nav-item {{ request()->routeIs('reportes.mi.*') ? 'menu-open' : '' }}"
                                data-menu="reportes">
                                <a href="#"
                                    class="nav-link {{ request()->routeIs('reportes.mi.*') ? 'active' : '' }}">
                                    <i class="nav-icon fas fa-file-pdf"></i>
                                    <p>
                                        Reportes
                                        <i class="right fas fa-angle-left"></i>
                                    </p>
                                </a>
                                <ul class="nav nav-treeview">
                                    <li class="nav-item">
                                        <a href="{{ route('reportes.mi.bitacora.pdf') }}" target="_blank"
                                            class="nav-link">
                                            <i class="nav-icon fas fa-file-pdf"></i>
                                            <p>Bitácora (PDF)</p>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="{{ route('reportes.mi.bitacora.excel') }}" class="nav-link">
                                            <i class="nav-icon fas fa-file-excel"></i>
                                            <p>Bitácora (Excel)</p>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        @if ($pasanteSesion->informeGenerado())
                                            <a href="{{ route('reportes.mi.informe') }}" target="_blank"
                                                class="nav-link">
                                                <i class="nav-icon fas fa-file-signature"></i>
                                                <p>Informe final</p>
                                            </a>
                                        @else
                                            <a href="#" class="nav-link btn-informe-final"
                                                data-completo="{{ $pasanteSesion->completoPracticas() ? '1' : '0' }}">
                                                <i class="nav-icon fas fa-file-signature"></i>
                                                <p>Informe final</p>
                                            </a>
                                        @endif
                                    </li>
                                </ul>
                            </li>
                        @endif

                        {{-- Gestión de Usuarios --}}
                        @can('ver-usuarios')
                            <li class="nav-item {{ request()->routeIs('usuarios.*') ? 'menu-open' : '' }}">
                                <a href="#"
                                    class="nav-link {{ request()->routeIs('usuarios.*') ? 'active' : '' }}">
                                    <i class="nav-icon fas fa-user"></i>
                                    <p>
                                        Gestión de Usuarios
                                        <i class="right fas fa-angle-left"></i>
                                    </p>
                                </a>
                                <ul class="nav nav-treeview">
                                    @can('crear-usuarios')
                                        <li class="nav-item">
                                            <a href="{{ route('usuarios.create') }}"
                                                class="nav-link {{ request()->routeIs(['usuarios.create', 'usuarios.edit']) ? 'active' : '' }}">
                                                <i class="fas fa-plus-circle nav-icon"></i>
                                                <p>Nuevo Usuario</p>
                                            </a>
                                        </li>
                                    @endcan
                                    <li class="nav-item">
                                        <a href="{{ route('usuarios.index') }}"
                                            class="nav-link {{ request()->routeIs(['usuarios.index', 'usuarios.show']) ? 'active' : '' }}">
                                            <i class="fas fa-list nav-icon"></i>
                                            <p>Lista de Usuarios</p>
                                        </a>
                                    </li>
                                </ul>
                            </li>
                        @endcan
                        {{-- Gestión de Pasantes --}}
@can('ver-pasantes')
                            <li class="nav-item {{ request()->routeIs('pasantes.*') ? 'menu-open' : '' }}">
                                <a href="#"
                                    class="nav-link {{ request()->routeIs('pasantes.*') ? 'active' : '' }}">
                                    <i class="nav-icon fas fa-users"></i>
                                    <p>
                                        Gestión de Pasantes
                                        <i class="right fas fa-angle-left"></i>
                                    </p>
                                </a>
                                <ul class="nav nav-treeview">
                                    @can('crear-pasantes')
                                        <li class="nav-item">
                                            <a href="{{ route('pasantes.create') }}"
                                                class="nav-link {{ request()->routeIs(['pasantes.create', 'pasantes.edit']) ? 'active' : '' }}">
                                                <i class="fas fa-plus-circle nav-icon"></i>
                                                <p>Nuevo Pasante</p>
                                            </a>
                                        </li>
                                    @endcan
                                    <li class="nav-item">
                                        <a href="{{ route('pasantes.index') }}"
                                            class="nav-link {{ request()->routeIs(['pasantes.index', 'pasantes.show']) ? 'active' : '' }}">
                                            <i class="fas fa-list nav-icon"></i>
                                            <p>Lista de Pasantes</p>
                                        </a>
                                    </li>
                                </ul>
                            </li>
                        @endcan

                        {{-- Gestión de Carreras --}}
                        @can('ver-carreras')
                            <li class="nav-item {{ request()->routeIs('carreras.*') ? 'menu-open' : '' }}">
                                <a href="#"
                                    class="nav-link {{ request()->routeIs('carreras.*') ? 'active' : '' }}">
                                    <i class="nav-icon fas fa-graduation-cap"></i>
                                    <p>
                                        Gestión de Carreras
                                        <i class="right fas fa-angle-left"></i>
                                    </p>
                                </a>
                                <ul class="nav nav-treeview">
                                    @can('crear-carreras')
                                        <li class="nav-item">
                                            <a href="{{ route('carreras.create') }}"
                                                class="nav-link {{ request()->routeIs(['carreras.create', 'carreras.edit']) ? 'active' : '' }}">
                                                <i class="fas fa-plus-circle nav-icon"></i>
                                                <p>Nueva Carrera</p>
                                            </a>
                                        </li>
                                    @endcan
                                    <li class="nav-item">
                                        <a href="{{ route('carreras.index') }}"
                                            class="nav-link {{ request()->routeIs(['carreras.index', 'carreras.show']) ? 'active' : '' }}">
                                            <i class="fas fa-list nav-icon"></i>
                                            <p>Lista de Carreras</p>
                                        </a>
                                    </li>
                                </ul>
                            </li>
                        @endcan

                        {{-- Gestión de Tutores --}}
                        @can('ver-tutores')
                            <li class="nav-item {{ request()->routeIs('tutores.*') ? 'menu-open' : '' }}">
                                <a href="#" class="nav-link {{ request()->routeIs('tutores.*') ? 'active' : '' }}">
                                    <i class="nav-icon fas fa-chalkboard-teacher"></i>
                                    <p>
                                        Gestión de Tutores
                                        <i class="right fas fa-angle-left"></i>
                                    </p>
                                </a>
                                <ul class="nav nav-treeview">
                                    @can('crear-tutores')
                                        <li class="nav-item">
                                            <a href="{{ route('tutores.create') }}"
                                                class="nav-link {{ request()->routeIs(['tutores.create', 'tutores.edit']) ? 'active' : '' }}">
                                                <i class="fas fa-plus-circle nav-icon"></i>
                                                <p>Nuevo Tutor</p>
                                            </a>
                                        </li>
                                    @endcan
                                    <li class="nav-item">
                                        <a href="{{ route('tutores.index') }}"
                                            class="nav-link {{ request()->routeIs(['tutores.index', 'tutores.show']) ? 'active' : '' }}">
                                            <i class="fas fa-list nav-icon"></i>
                                            <p>Lista de Tutores</p>
                                        </a>
                                    </li>
                                </ul>
                            </li>
                        @endcan

                        {{-- Gestión de Asistencias --}}
                        @can('ver-pasantes')
                            <li class="nav-item {{ request()->routeIs('asistencias.*') ? 'menu-open' : '' }}">
                                <a href="#"
                                    class="nav-link {{ request()->routeIs('asistencias.*') ? 'active' : '' }}">
                                    <i class="nav-icon fas fa-clock"></i>
                                    <p>
                                        Gestión de Asistencias
                                        <i class="right fas fa-angle-left"></i>
                                    </p>
                                </a>
                                <ul class="nav nav-treeview">
                                    @can('crear-pasantes')
                                        <li class="nav-item">
                                            <a href="{{ route('asistencias.create') }}"
                                                class="nav-link {{ request()->routeIs(['asistencias.create', 'asistencias.edit']) ? 'active' : '' }}">
                                                <i class="fas fa-plus-circle nav-icon"></i>
                                                <p>Registrar Asistencia</p>
                                            </a>
                                        </li>
                                    @endcan
                                    <li class="nav-item">
                                        <a href="{{ route('asistencias.index') }}"
                                            class="nav-link {{ request()->routeIs(['asistencias.index', 'asistencias.show']) ? 'active' : '' }}">
                                            <i class="fas fa-list nav-icon"></i>
                                            <p>Lista de Asistencias</p>
                                        </a>
                                    </li>
                                    </ul>
                            </li>
                        @endcan

                        {{-- Gestión de Reportes --}}
                        @can('ver-reporte-asistencias')
                            <li class="nav-item">
                                <a href="{{ route('reportes.index') }}"
                                    class="nav-link {{ request()->routeIs('reportes.*') ? 'active' : '' }}">
                                    <i class="nav-icon fas fa-file-alt"></i>
                                    <p>Gestión de Reportes</p>
                                </a>
                            </li>
                        @endcan

                        {{-- Gestión de Roles y Permisos --}}
                        @can('ver-roles')
                            <li
                                class="nav-item {{ request()->routeIs('roles.*') || request()->routeIs('permisos.*') ? 'menu-open' : '' }}">
                                <a href="#"
                                    class="nav-link {{ request()->routeIs('roles.*') || request()->routeIs('permisos.*') ? 'active' : '' }}">
                                    <i class="nav-icon fas fa-user-shield"></i>
                                    <p>
                                        Roles y Permisos
                                        <i class="right fas fa-angle-left"></i>
                                    </p>
                                </a>
                                <ul class="nav nav-treeview">
                                    <li class="nav-item">
                                        <a href="{{ route('roles.index') }}"
                                            class="nav-link {{ request()->routeIs(['roles.index', 'roles.show', 'roles.edit']) ? 'active' : '' }}">
                                            <i class="fas fa-user-shield nav-icon"></i>
                                            <p>Administrar Roles</p>
                                        </a>
                                    </li>
                                    @can('ver-permisos')
                                        <li class="nav-item">
                                            <a href="{{ route('permisos.index') }}"
                                                class="nav-link {{ request()->routeIs('permisos.*') ? 'active' : '' }}">
                                                <i class="fas fa-key nav-icon"></i>
                                                <p>Lista de Permisos</p>
                                            </a>
                                        </li>
                                    @endcan
                                </ul>
                            </li>
                        @endcan
                    </ul>
                </nav>
                <!-- /.sidebar-menu -->
            </div>
            <!-- /.sidebar -->
        </aside>

        <!-- Content Wrapper. Contains page content -->
        <div class="content-wrapper">
            <br>
            <div class="container-fluid px-4">
                @yield('content')

            </div>
        </div>
        <!-- /.content-wrapper -->

        <!-- Main Footer -->
        <footer class="main-footer">
            <!-- To the right -->
            <div class="float-right d-none d-sm-inline">
                <strong>Tecnologias De la Informacion</strong>
            </div>
            <!-- Default to the left -->
            <strong>Copyright &copy; 2026 <a href="https://vidanueva.edu.ec/">Tecnologio Universitario Vida
                    Nueva</a>.</strong> All rights
            reserved.
        </footer>
    </div>
    <!-- ./wrapper -->

    <!-- REQUIRED SCRIPTS -->

    <!-- jQuery -->
    <script src="{{ asset('plugins/jquery/jquery.min.js') }}"></script>
    <!-- Bootstrap 4 -->
    <script src="{{ asset('plugins/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <!-- AdminLTE App -->
    <script src="{{ asset('dist/js/adminlte.min.js') }}"></script>
    <!-- DataTables  & Plugins -->
    <script src="{{ asset('plugins/datatables/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('plugins/datatables-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('plugins/datatables-responsive/js/dataTables.responsive.min.js') }}"></script>
    <script src="{{ asset('plugins/datatables-responsive/js/responsive.bootstrap4.min.js') }}"></script>
    <!-- Confirmaciones de eliminacion compartidas -->
    <script src="{{ asset('js/confirmaciones.js') }}"></script>

    @stack('scripts')

    {{-- Aviso del informe final propio: solo se descarga cuando la
         administracion ya lo genero; si no, se informa al pasante. --}}
    <script>
        $(function() {
            $('.btn-informe-final').on('click', function(e) {
                e.preventDefault();

                var completo = String($(this).data('completo')) === '1';

                if (! completo) {
                    mostrarToast('error', 'Error al generar informe final',
                        'Aún no has completado tus horas de prácticas.');
                    return;
                }

                mostrarToast('info', 'Informe final',
                    'Tus horas están completas: el informe lo genera la administración.');
            });

            {{-- Al elegir un reporte (descarga), la prioridad del menu cambia a
                 "Reportes": se pinta el encabezado y se desmarca "Asistencia".
                 Los subitems son acciones, no se marcan (no hay pagina a la que
                 pertenezcan). El submenu se mantiene desplegado. --}}
            $('[data-menu="reportes"]').on('click', '.nav-link', function() {
                var $reportes = $('[data-menu="reportes"]');

                $reportes.children('.nav-link').addClass('active');
                $reportes.find('.nav-treeview .nav-link').removeClass('active');

                $('[data-menu="asistencia"]')
                    .removeClass('menu-open')
                    .children('.nav-link').removeClass('active');
            });
        });
    </script>

</body>

</html>
