@extends('layouts.admin')
@section('content')
    <h1>Editar Pasante</h1>

    <div class="card card-outline card-warning">
        <div class="card-header">
            <h3 class="card-title">Formulario de Edición</h3>
        </div>
        <div class="card-body">

            {{-- enctype="multipart/form-data" es necesario para subir archivos --}}
            <form action="{{ route('pasantes.update', $pasante->id) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                @if ($errors->any())
                    @include('partials.toast', [
                        'icono' => 'warning',
                        'titulo' => 'Revisa los datos',
                        'mensaje' => $errors->first(),
                    ])
                @endif
                <div class="row">

                    {{-- Columna izquierda: campos del formulario --}}
                    <div class="col-md-8">

                        {{-- Cedula y Telefono --}}
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Cedula</label>
                                    <input type="number" class="form-control" name="cedula" maxlength="10"
                                        placeholder="1234567890" value="{{ old('cedula', $pasante->cedula) }}" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Telefono</label>
                                    <input type="number" class="form-control" name="telefono" maxlength="15"
                                        placeholder="0991234567" value="{{ old('telefono', $pasante->telefono) }}" required>
                                </div>
                            </div>
                        </div>

                        {{-- Nombres y Apellidos --}}
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Nombres</label>
                                    <input type="text" class="form-control" name="nombres"
                                        placeholder="Nombres completos" value="{{ old('nombres', $pasante->nombres) }}" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Apellidos</label>
                                    <input type="text" class="form-control" name="apellidos"
                                        placeholder="Apellidos completos" value="{{ old('apellidos', $pasante->apellidos) }}" required>
                                </div>
                            </div>
                        </div>

                        {{-- Carrera y Horas de practica --}}
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Carrera</label>
                                    <select class="form-control" name="carrera_id" id="carrera_id" required>
                                        <option value="">Seleccione una carrera</option>
                                        @foreach ($carreras as $carrera)
                                            <option value="{{ $carrera->id }}"
                                                data-horas="{{ $carrera->horas_practicas }}"
                                                {{ old('carrera_id', $pasante->carrera_id) == $carrera->id ? 'selected' : '' }}>
                                                {{ $carrera->nombre }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Horas de Practica</label>
                                    <input type="number" class="form-control" id="horas_practicas" name="horas_practicas"
                                        placeholder="Se completa solo" readonly tabindex="-1"
                                        value="{{ old('horas_practicas', $pasante->carrera->horas_practicas) }}">
                                </div>
                            </div>
                        </div>

                        {{-- Tutor y Fecha de Ingreso --}}
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Tutor</label>
                                    <select class="form-control" name="tutor_id" id="tutor_id" required>
                                        <option value="">Seleccione un tutor</option>
                                        @foreach ($tutores as $tutor)
                                            <option value="{{ $tutor->id }}"
                                                data-carrera="{{ $tutor->carrera_id }}"
                                                {{ old('tutor_id', $pasante->tutor_id) == $tutor->id ? 'selected' : '' }}>
                                                {{ $tutor->nombres }} {{ $tutor->apellidos }} ({{ $tutor->codigo_docente }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Fecha de Ingreso</label>
                                    <input type="date" class="form-control" name="fecha_ingreso"
                                        value="{{ old('fecha_ingreso', $pasante->fecha_ingreso->format('Y-m-d')) }}" required>
                                </div>
                            </div>
                        </div>

                        {{-- Email --}}
                        <div class="form-group">
                            <label>Email</label>
                            <div class="input-group mb-3">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                </div>
                                <input type="email" class="form-control" name="email" placeholder="correo@ejemplo.com"
                                    value="{{ old('email', $pasante->email) }}" required>
                            </div>
                        </div>

                        {{-- Estado --}}
                        <div class="form-group">
                            <label>Estado</label>
                            <select class="form-control" name="estado" required>
                                <option value="1" {{ old('estado', $pasante->estado) == '1' ? 'selected' : '' }}>Activo</option>
                                <option value="0" {{ old('estado', $pasante->estado) == '0' ? 'selected' : '' }}>Inactivo</option>
                            </select>
                        </div>

                        {{-- Botones --}}
                        <div class="mt-3">
                            <a href="{{ route('pasantes.index') }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-left mr-1"></i> Volver
                            </a>
                            <button type="submit" class="btn btn-warning">
                                <i class="fas fa-save mr-1"></i> Actualizar Pasante
                            </button>
                        </div>

                    </div>

                    {{-- Columna derecha: solo foto --}}
                    <div class="col-md-4 text-center">
                        <label>Foto del Pasante</label>
                        <div>
                            @if ($pasante->foto)
                                <img id="preview" src="{{ route('pasantes.foto', $pasante) }}" alt="Foto"
                                    class="img-fluid img-thumbnail mb-2" style="max-height: 220px;">
                            @else
                                <img id="preview" src="{{ asset('img/Avatar-Default-Img.jpg') }}" alt="Foto"
                                    class="img-fluid img-thumbnail mb-2" style="max-height: 220px;">
                            @endif
                        </div>
                        <input type="file" class="form-control-file" name="foto" id="foto" accept="image/*">
                        <small class="text-muted d-block mt-1">JPG o PNG, max 2MB. Si no seleccionas foto, se conserva la actual.</small>
                    </div>

                </div>

                </form>

        </div>
    </div>

    {{-- Script para previsualizar la foto, autocompletar las horas y filtrar los tutores por carrera --}}
    <script>
        document.getElementById('foto').addEventListener('change', function(event) {
            var archivo = event.target.files[0];
            if (archivo) {
                var lector = new FileReader();
                lector.onload = function(e) {
                    document.getElementById('preview').src = e.target.result;
                };
                lector.readAsDataURL(archivo);
            }
        });

        (function() {
            var selectCarrera = document.getElementById('carrera_id');
            var inputHoras = document.getElementById('horas_practicas');
            var selectTutor = document.getElementById('tutor_id');
            var opcionesTutor = Array.prototype.slice.call(selectTutor.options);

            function sincronizarHoras() {
                var opcion = selectCarrera.options[selectCarrera.selectedIndex];

                inputHoras.value = opcion && opcion.dataset.horas ? opcion.dataset.horas : '';
            }

            // Muestra solo los tutores de la carrera elegida. El tutor guardado
            // se conserva en la carga inicial aunque no coincida, para no perder
            // la asignacion al editar.
            function filtrarTutores(inicial) {
                var carrera = selectCarrera.value;
                var seleccionado = selectTutor.value;

                opcionesTutor.forEach(function(opcion) {
                    if (!opcion.value) {
                        return;
                    }

                    var esActual = inicial && opcion.value === seleccionado;
                    var coincide = !carrera || opcion.dataset.carrera === carrera;
                    var mostrar = coincide || esActual;

                    opcion.hidden = !mostrar;
                    opcion.disabled = !mostrar;
                });

                if (!inicial) {
                    var elegido = selectTutor.options[selectTutor.selectedIndex];

                    if (elegido && elegido.disabled) {
                        selectTutor.value = '';
                    }
                }
            }

            selectCarrera.addEventListener('change', function() {
                sincronizarHoras();
                filtrarTutores(false);
            });

            sincronizarHoras();
            filtrarTutores(true);
        })();
    </script>
@endsection
