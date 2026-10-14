@extends('layouts.admin')
@section('content')
    <h1>Editar Asistencia</h1>

    @if (session('confirmacion'))
        @include('partials.toast', [
            'icono' => 'success',
            'titulo' => '¡Éxito!',
            'mensaje' => session('confirmacion'),
        ])
    @endif

    <div class="card card-outline card-warning">
        <div class="card-header">
            <h3 class="card-title">
                Editar el Turno #{{ $asistencia->id }}
            </h3>
        </div>
        <div class="card-body">
            <div class="row">

                <form action="{{ route('asistencias.update', $asistencia->id) }}" method="POST">
                    @csrf
                    @method('PUT')
                    @if ($errors->any())
                        @include('partials.toast', [
                            'icono' => 'warning',
                            'titulo' => 'Revisa los datos',
                            'mensaje' => $errors->first(),
                        ])
                    @endif

                    {{-- El pasante no se cambia: si el turno se creo con el error de pasante equivocado,
                     se elimina y se registra de nuevo --}}
                    <input type="hidden" name="pasante_id" value="{{ $asistencia->pasante_id }}">

                    <div class="row">

                        {{-- Datos del pasante, solo lectura --}}
                        <div class="col-md-7">

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Pasante</label>
                                        <input type="text" class="form-control" readonly tabindex="-1"
                                            value="{{ $asistencia->pasante->apellidos }} {{ $asistencia->pasante->nombres }}">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Cedula</label>
                                        <input type="text" class="form-control" readonly tabindex="-1"
                                            value="{{ $asistencia->pasante->cedula }}">
                                    </div>
                                </div>
                            </div>

                            {{-- Solo se corrigen fecha, jornada y horas --}}
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Fecha</label>
                                        <input type="date" class="form-control" name="fecha" required
                                            value="{{ old('fecha', $asistencia->fecha->format('Y-m-d')) }}">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Jornada</label>
                                        <select class="form-control" name="jornada" required>
                                            @foreach ($jornadas as $valor => $texto)
                                                <option value="{{ $valor }}"
                                                    {{ old('jornada', $asistencia->jornada) == $valor ? 'selected' : '' }}>
                                                    {{ $texto }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Hora de Entrada</label>
                                        <input type="time" class="form-control" name="hora_entrada" required
                                            value="{{ old('hora_entrada', $asistencia->hora_entrada) }}">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Hora de Salida</label>
                                        <input type="time" class="form-control" name="hora_salida"
                                            value="{{ old('hora_salida', $asistencia->hora_salida) }}">
                                        <small class="text-muted">Dejelo vacio si el pasante no ha salido</small>
                                    </div>
                                </div>
                            </div>

                            {{-- Botones --}}
                            <div class="mt-3">
                                <a href="{{ route('asistencias.index') }}" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left mr-1"></i> Volver
                                </a>
                                <button type="submit" class="btn btn-warning">
                                    Actualizar Asistencia <i class="fas fa-save mr-1"></i>
                                </button>
                            </div>

                        </div>

                        {{-- Columna derecha: las fotos del registro original, no editables --}}
                        <div class="col-md-5">
                            <div class="row">
                                <label>Fotos del registro</label>
                                <p class="text-muted small">
                                    Las fotos no se modifican desde aqui. Para cambiarlas, elimine el turno
                                    y registre uno nuevo.
                                </p>

                                <div class="col-md-6 text-center mb-3">
                                    <small class="d-block text-muted mb-1">Entrada</small>
                                    @if ($asistencia->foto_entrada)
                                        <img src="{{ route('asistencias.foto', [$asistencia->id, 'entrada']) }}"
                                            alt="Foto de entrada" class="img-fluid img-thumbnail"
                                            style="max-height: 170px;">
                                    @else
                                        <img src="{{ asset('img/Avatar-Default-Img.jpg') }}" alt="Sin foto de entrada"
                                            class="img-fluid img-thumbnail" style="max-height: 170px;">
                                        <small class="text-muted d-block">Sin foto</small>
                                    @endif
                                </div>

                                <div class="col-md-6 text-center">
                                    <small class="d-block text-muted mb-1">Salida</small>
                                    @if ($asistencia->foto_salida)
                                        <img src="{{ route('asistencias.foto', [$asistencia->id, 'salida']) }}"
                                            alt="Foto de salida" class="img-fluid img-thumbnail" style="max-height: 170px;">
                                    @else
                                        <img src="{{ asset('img/Avatar-Default-Img.jpg') }}" alt="Sin foto de salida"
                                            class="img-fluid img-thumbnail" style="max-height: 170px;">
                                        <small class="text-muted d-block">Sin foto</small>
                                    @endif
                                </div>
                            </div>
                        </div>

                    </div>
                </form>

            </div>
        </div>
    </div>

    @endsection
