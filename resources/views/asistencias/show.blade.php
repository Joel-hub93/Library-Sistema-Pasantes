@extends('layouts.admin')
@section('content')
    <h1>Detalle de la Asistencia</h1>

    @if (session('confirmacion'))
        @include('partials.toast', [
            'icono' => 'success',
            'titulo' => '¡Éxito!',
            'mensaje' => session('confirmacion'),
        ])
    @endif

    <div class="card card-outline card-info">
        <div class="card-header">
            <h3 class="card-title">Información del Turno #{{ $asistencia->id }}</h3>
        </div>
        <div class="card-body">
            <div class="row">

                {{-- Columna izquierda: fotos --}}
                <div class="col-md-5">

                    <div class="row">
                        <div class="col-md-6 text-center">
                            <small class="d-block text-muted mb-1">Foto de Entrada</small>
                            @if ($asistencia->foto_entrada)
                                <a href="{{ route('asistencias.foto', [$asistencia->id, 'entrada']) }}" target="_blank">
                                    <img src="{{ route('asistencias.foto', [$asistencia->id, 'entrada']) }}"
                                        alt="Foto de entrada" class="img-fluid img-thumbnail" style="max-height: 200px;">
                                </a>
                            @else
                                <img src="{{ asset('img/Avatar-Default-Img.jpg') }}" alt="Sin foto de entrada"
                                    class="img-fluid img-thumbnail" style="max-height: 200px;">
                                <small class="text-muted d-block">Sin foto</small>
                            @endif
                        </div>
                        <div class="col-md-6 text-center">
                            <small class="d-block text-muted mb-1">Foto de Salida</small>
                            @if ($asistencia->foto_salida)
                                <a href="{{ route('asistencias.foto', [$asistencia->id, 'salida']) }}" target="_blank">
                                    <img src="{{ route('asistencias.foto', [$asistencia->id, 'salida']) }}"
                                        alt="Foto de salida" class="img-fluid img-thumbnail" style="max-height: 200px;">
                                </a>
                            @else
                                <img src="{{ asset('img/Avatar-Default-Img.jpg') }}" alt="Sin foto de salida"
                                    class="img-fluid img-thumbnail" style="max-height: 200px;">
                                <small class="text-muted d-block">Sin foto</small>
                            @endif
                        </div>
                    </div>

                    <h4 class="mt-3 mb-0 text-center">
                        {{ $asistencia->pasante->apellidos }} {{ $asistencia->pasante->nombres }}
                    </h4>
                    <small class="text-muted d-block text-center">
                        C.I. {{ $asistencia->pasante->cedula }} —
                        {{ $asistencia->pasante->carrera?->nombre }}
                    </small>

                    <div class="mt-3 text-center">
                        @if ($asistencia->completo())
                            <span class="badge badge-success">Turno completo</span>
                        @else
                            <span class="badge badge-warning">Salida no registrada</span>
                        @endif
                        <span class="badge badge-info">{{ $asistencia->estado() }}</span>
                    </div>

                    @can('editar-pasantes')
                        <div class="text-center mt-3">
                            <a href="{{ route('asistencias.edit', $asistencia->id) }}" class="btn btn-info">
                                <i class="fas fa-edit mr-1"></i> Editar
                            </a>
                        </div>
                    @endcan

                </div>

                {{-- Columna derecha: datos --}}
                <div class="col-md-7">
                    <table class="table table-striped table-bordered">
                        <tr>
                            <th style="width: 40%"><i class="fas fa-hashtag mr-2"></i>ID</th>
                            <td>{{ $asistencia->id }}</td>
                        </tr>
                        <tr>
                            <th><i class="fas fa-user-graduate mr-2"></i>Pasante</th>
                            <td>{{ $asistencia->pasante->apellidos }} {{ $asistencia->pasante->nombres }}</td>
                        </tr>
                        <tr>
                            <th><i class="fas fa-calendar-alt mr-2"></i>Fecha</th>
                            <td>
                                {{ $asistencia->fecha->format('d/m/Y') }}
                                <small class="text-muted text-capitalize">
                                    ({{ $asistencia->fecha->isoFormat('dddd') }})
                                </small>
                                @if ($asistencia->esFinDeSemana())
                                    <span class="badge badge-secondary ml-1">Fin de semana</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th><i class="fas fa-sun mr-2"></i>Jornada</th>
                            <td>{{ $jornadas[$asistencia->jornada] ?? $asistencia->jornada }}</td>
                        </tr>
                        <tr>
                            <th><i class="fas fa-sign-in-alt mr-2"></i>Hora de Entrada</th>
                            <td>{{ $asistencia->hora_entrada }}</td>
                        </tr>
                        <tr>
                            <th><i class="fas fa-sign-out-alt mr-2"></i>Hora de Salida</th>
                            <td>{{ $asistencia->hora_salida ?? 'Sin registrar' }}</td>
                        </tr>
                        <tr>
                            <th><i class="fas fa-clock mr-2"></i>Total Horas</th>
                            <td>{{ $asistencia->totalHoras() }}</td>
                        </tr>
                        <tr>
                            <th><i class="fas fa-check-circle mr-2"></i>Cumplimiento</th>
                            <td>{{ gmdate('H:i', $asistencia->minutosCumplimiento() * 60) }} de 04:00</td>
                        </tr>
                        @if ($asistencia->minutosApoyo() > 0)
                            <tr>
                                <th><i class="fas fa-hand-holding-heart mr-2"></i>Apoyo al area</th>
                                <td>{{ gmdate('H:i', $asistencia->minutosApoyo() * 60) }}</td>
                            </tr>
                        @endif
                        <tr>
                            <th><i class="fas fa-info-circle mr-2"></i>Registrado el</th>
                            <td>{{ $asistencia->created_at->format('d/m/Y H:i') }}</td>
                        </tr>
                        <tr>
                            <th><i class="fas fa-edit mr-2"></i>Ultima actualizacion</th>
                            <td>{{ $asistencia->updated_at->format('d/m/Y H:i') }}</td>
                        </tr>
                    </table>
                </div>

            </div>

            {{-- Botones --}}
            <div class="mt-3">
                <a href="{{ route('asistencias.index') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left mr-1"></i> Volver
                </a>
            </div>
        </div>
    </div>
@endsection
