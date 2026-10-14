@extends('layouts.admin')
@section('content')
    <h1>Registrar Asistencia</h1>

    <div class="card card-outline card-success">
        <div class="card-header">
            <h3 class="card-title">Formulario de Registro</h3>
        </div>
        <div class="card-body">

            <form action="{{ route('asistencias.store') }}" method="POST">
                @csrf

                @if (session('error'))
                    @include('partials.toast', [
                        'icono' => 'error',
                        'titulo' => 'No se pudo registrar',
                        'mensaje' => session('error'),
                    ])
                @endif

                @if ($errors->any())
                    @include('partials.toast', [
                        'icono' => 'warning',
                        'titulo' => 'Revisa los datos',
                        'mensaje' => $errors->first(),
                    ])
                @endif

                <div class="row">

                    {{-- Datos del turno --}}
                    <div class="col-12">

                        {{-- Pasante y datos readonly del pasante elegido --}}
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Pasante</label>
                                    <select class="form-control" name="pasante_id" id="pasante_id" required>
                                        <option value="">Seleccione un pasante</option>
                                        @foreach ($pasantes as $pasante)
                                            @php($cerrado = $pasante->reportes->isNotEmpty())
                                            <option value="{{ $pasante->id }}"
                                                data-cedula="{{ $pasante->cedula }}"
                                                data-carrera="{{ $pasante->carrera?->nombre }}"
                                                {{ $cerrado ? 'disabled' : '' }}
                                                {{ old('pasante_id') == $pasante->id ? 'selected' : '' }}>
                                                {{ $pasante->apellidos }} {{ $pasante->nombres }}
                                                {{ $cerrado ? '(Informe generado - periodo cerrado)' : '' }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <small class="text-muted">
                                        Los pasantes con informe final generado ya no aceptan registros.
                                    </small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Cedula</label>
                                    <input type="text" class="form-control" id="cedula"
                                        placeholder="Se completa solo" readonly tabindex="-1"
                                        value="{{ old('cedula') }}">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Carrera</label>
                                    <input type="text" class="form-control" id="carrera"
                                        placeholder="Se completa solo" readonly tabindex="-1"
                                        value="{{ old('carrera') }}">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Jornada</label>
                                    <select class="form-control" name="jornada" id="jornada" required>
                                        <option value="">Seleccione una jornada</option>
                                        @foreach ($jornadas as $valor => $texto)
                                            <option value="{{ $valor }}" {{ old('jornada') == $valor ? 'selected' : '' }}>
                                                {{ $texto }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>

                        {{-- Fecha y horas del turno. El total y el apoyo se calculan
                             solos al guardar, por eso no se muestran aqui --}}
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Fecha</label>
                                    <input type="date" class="form-control" name="fecha" required
                                        value="{{ old('fecha', now()->format('Y-m-d')) }}">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Hora de Entrada</label>
                                    <input type="time" class="form-control" name="hora_entrada" required
                                        value="{{ old('hora_entrada') }}">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Hora de Salida</label>
                                    <input type="time" class="form-control" name="hora_salida"
                                        value="{{ old('hora_salida') }}">
                                    <small class="text-muted">Dejelo vacio si el pasante no ha salido</small>
                                </div>
                            </div>
                        </div>

                        {{-- Botones --}}
                        <div class="mt-3">
                            <a href="{{ route('asistencias.index') }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-left mr-1"></i> Volver
                            </a>
                            <button type="submit" class="btn btn-success">
                                Guardar Asistencia <i class="fas fa-save mr-1"></i>
                            </button>
                        </div>

                    </div>

                </div>
            </form>

        </div>
    </div>

    <script>
        (function() {
            var selectPasante = document.getElementById('pasante_id');
            var cedula = document.getElementById('cedula');
            var carrera = document.getElementById('carrera');

            function sincronizarPasante() {
                var opcion = selectPasante.options[selectPasante.selectedIndex];

                cedula.value = opcion && opcion.dataset.cedula ? opcion.dataset.cedula : '';
                carrera.value = opcion && opcion.dataset.carrera ? opcion.dataset.carrera : '';
            }

            selectPasante.addEventListener('change', sincronizarPasante);
            sincronizarPasante();
        })();
    </script>
@endsection
