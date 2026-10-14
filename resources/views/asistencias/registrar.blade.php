@extends('layouts.admin')

@section('content')
    <h1>Registrar asistencia</h1>

    {{-- Avisos de la sesion: toasts en la esquina superior derecha --}}
    @if (session('confirmacion'))
        @include('partials.toast', [
            'icono' => 'success',
            'titulo' => '¡Asistencia registrada!',
            'mensaje' => session('confirmacion'),
        ])
    @endif

    @if (session('error'))
        @include('partials.toast', [
            'icono' => 'error',
            'titulo' => 'No se pudo registrar',
            'mensaje' => session('error'),
        ])
    @endif

    {{-- isset porque un fallo de validacion nunca debe tumbar la pantalla --}}
    @if (isset($errors) && $errors->any())
        @include('partials.toast', [
            'icono' => 'warning',
            'titulo' => 'Revisa los datos',
            'mensaje' => $errors->first(),
        ])
    @endif

    @unless ($diaHabil)
        <div class="alert alert-warning">
            <i class="fas fa-ban mr-1"></i>
            Hoy es {{ now()->translatedFormat('l') }}. No se registran asistencias
            en sábado ni domingo.
        </div>
    @endunless

    {{-- El informe final cierra el periodo: ya no se aceptan registros --}}
    @if ($periodoCerrado)
        <div class="alert alert-secondary">
            <i class="fas fa-file-pdf mr-1"></i>
            <strong>Tu informe final ya fue generado.</strong>
            El periodo de registro de asistencias quedo cerrado; aqui solo
            puedes consultar tus datos.
        </div>
    @endif

    <div class="card card-outline card-primary">
        <div class="card-header">
            <h3 class="card-title">Registro diario</h3>
        </div>
        <div class="card-body">

            {{-- enctype="multipart/form-data" es necesario para subir la foto --}}
            <form id="formularioMarcado" action="{{ route('asistencias.marcar') }}"
                method="POST" enctype="multipart/form-data" data-cerrado="{{ $periodoCerrado ? '1' : '0' }}">
                @csrf

                {{-- La foto se captura con la camara y se inyecta en este campo --}}
                <input type="file" name="foto" id="foto" accept="image/*" class="d-none">

                {{-- Estado por jornada para bloquear entrada/salida en el cliente --}}
                <script type="application/json" id="estado-jornadas">
                    {!! json_encode($estadoJornadas) !!}
                </script>

                <div class="row">

                    {{-- Columna izquierda: campos de la marca, como el formulario de pasantes --}}
                    <div class="col-md-8">

                        {{-- Pasante y Carrera --}}
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Pasante</label>
                                    <input type="text" class="form-control"
                                        value="{{ $pasante->apellidos }} {{ $pasante->nombres }}" readonly>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Carrera</label>
                                    <input type="text" class="form-control"
                                        value="{{ $pasante->carrera?->nombre ?? 'Carrera no asignada' }}" readonly>
                                </div>
                            </div>
                        </div>

                        {{-- Horas de practica y Jornada --}}
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Horas de Práctica</label>
                                    <input type="text" class="form-control"
                                        value="{{ $pasante->horasRequeridas() }}" readonly>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Jornada</label>
                                    <select class="form-control" name="jornada" id="jornada" required>
                                        @foreach ($jornadas as $valor => $texto)
                                            <option value="{{ $valor }}" @selected(old('jornada', $jornadaSeleccionada) === $valor)>{{ $texto }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>

                        {{-- Fecha y Hora juntas, un poco mas grandes que los demas campos --}}
                        <div class="form-group">
                            <label>Fecha y Hora</label>
                            <div class="input-group input-group-lg">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-calendar-day"></i></span>
                                </div>
                                <input type="text" class="form-control" id="fecha"
                                    value="{{ now()->format('d/m/Y') }}" readonly>
                                <div class="input-group-append">
                                    <span class="input-group-text"><i class="fas fa-clock"></i></span>
                                </div>
                                <input type="text" class="form-control" id="reloj"
                                    value="{{ now()->format('H:i:s') }}" readonly>
                            </div>
                        </div>

                        {{-- Entrada o Salida: gris (contorno) cuando no esta activa y con color al activarse --}}
                        <div class="form-group">
                            <label>¿Qué vas a marcar?</label>
                            @php($estado = $estadoJornadas[$jornadaSeleccionada])
                            <div class="btn-group btn-group-toggle d-flex w-100" data-toggle="buttons">
                                <label class="btn btn-outline-info flex-fill {{ ! $estado['entrada'] ? 'active' : '' }}">
                                    <input type="radio" name="tipo" value="entrada" class="sr-only" {{ $estado['entrada'] ? 'disabled' : 'checked' }}>
                                    <i class="fas fa-sign-in-alt"></i> Entrada
                                </label>
                                <label class="btn btn-outline-warning flex-fill {{ $estado['entrada'] && ! $estado['completo'] ? 'active' : '' }}">
                                    <input type="radio" name="tipo" value="salida" class="sr-only" {{ $estado['entrada'] && ! $estado['completo'] ? 'checked' : 'disabled' }}>
                                    <i class="fas fa-sign-out-alt"></i> Salida
                                </label>
                            </div>
                        </div>

                        {{-- Botones: Volver plomo y Registrar verde, como los otros formularios --}}
                        <div class="mt-3">
                            <a href="{{ route('asistencias.historial') }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-left mr-1"></i> Volver
                            </a>
                            <button type="submit" class="btn btn-success" id="btnGuardar" disabled>
                                <i class="fas fa-check mr-1"></i> <span id="textoGuardar">Registrar</span>
                            </button>
                        </div>

                    </div>

                    {{-- Columna derecha: foto con el avatar por defecto --}}
                    <div class="col-md-4 text-center">
                        <label>Foto de la asistencia</label>
                        <div>
                            <img id="instantanea" src="{{ asset('img/Avatar-Default-Img.jpg') }}"
                                data-avatar="{{ asset('img/Avatar-Default-Img.jpg') }}"
                                alt="Foto de la asistencia"
                                class="img-fluid img-thumbnail mb-2" style="max-height: 220px;">
                            <video id="camara" class="d-none w-100" playsinline muted autoplay></video>
                        </div>

                        <div class="alert alert-info d-none mt-2 mb-2 py-2" id="avisoCamara"></div>

                        {{-- Controles de la camara: como los botones de accion de las tablas --}}
                        <div class="mt-2">
                            <button type="button" class="btn btn-info btn-sm" id="btnCamara" title="Usar video">
                                <i class="fas fa-video"></i>
                            </button>
                            <button type="button" class="btn btn-success btn-sm" id="btnTomar" disabled title="Tomar foto">
                                <i class="fas fa-camera"></i>
                            </button>
                            <button type="button" class="btn btn-warning btn-sm" id="btnRepetir" disabled title="Repetir">
                                <i class="fas fa-undo"></i>
                            </button>
                        </div>

                        {{-- Respaldo para navegadores sin camara o sin HTTPS --}}
                        <div class="mt-2 d-none" id="respaldo">
                            <hr>
                            <label class="btn btn-outline-secondary btn-block btn-sm">
                                <i class="fas fa-image mr-1"></i> Elegir una foto de la galeria
                                <input type="file" id="fotoGaleria" accept="image/*"
                                    capture="environment" class="d-none">
                            </label>
                        </div>
                    </div>

                </div>

            </form>

        </div>
    </div>

@endsection

@push('scripts')
    <script src="{{ asset('js/marcado.js') }}"></script>
@endpush