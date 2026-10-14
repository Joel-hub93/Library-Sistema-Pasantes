@extends('layouts.admin')

@push('estilos')
    <style>
        .registro-hoy .foto-turno {
            max-height: 180px;
        }

        .registro-hoy .table {
            table-layout: fixed;
        }

        .registro-hoy .table th,
        .registro-hoy .table td {
            width: 25%;
        }
    </style>
@endpush

@section('content')
    <h1>Asistencia Registrada</h1>

    <div class="card card-outline card-primary registro-hoy">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h3 class="card-title">
                    <i class="fas fa-calendar-day mr-1"></i>
                    {{ now()->translatedFormat('l') }} {{ now()->format('d/m/Y') }}
                </h3>
                <div class="d-flex">
                    <a href="{{ route('asistencias.historial') }}" class="btn btn-primary mr-2">
                        <i class="fas fa-history mr-1"></i> Historial
                    </a>
                    <a href="{{ route('asistencias.registrar') }}" class="btn btn-success">
                        <i class="fas fa-camera-retro mr-1"></i> Registrarme
                    </a>
                </div>
            </div>
        </div>

        <div class="card-body">

            {{-- Mismo patron de avisos que el resto del proyecto --}}
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
                    'titulo' => 'Error',
                    'mensaje' => session('error'),
                ])
            @endif

            @if ($turnosHoy->isEmpty())
                <div class="alert alert-info">
                    <i class="fas fa-inbox mr-1"></i>
                    Todavía no tienes ningún turno registrado hoy.
                </div>

            @else
                @foreach ($turnosHoy as $turno)
                    <div class="card mb-3">
                        <div class="card-header">
                            <h3 class="card-title">
                                {{ $jornadas[$turno->jornada] ?? $turno->jornada }}
                                <span class="badge badge-{{ $turno->completo() ? 'success' : 'warning' }} ml-2">
                                    {{ $turno->completo() ? 'Completo' : 'Falta la salida' }}
                                </span>
                            </h3>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered mb-0">
                                    <thead>
                                        <tr>
                                            <th>Entrada</th>
                                            <th>Salida</th>
                                            <th>Total</th>
                                            <th>Apoyo al área</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>{{ $turno->hora_entrada }}</td>
                                            <td>
                                                @if ($turno->hora_salida)
                                                    {{ $turno->hora_salida }}
                                                @else
                                                    <span class="badge badge-warning">Sin salida</span>
                                                @endif
                                            </td>
                                            <td>{{ $turno->totalHoras() }}</td>
                                            <td>
                                                @if ($turno->minutosApoyo() > 0)
                                                    {{ gmdate('H:i', $turno->minutosApoyo() * 60) }}
                                                @else
                                                    <span class="text-muted">—</span>
                                                @endif
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            @unless ($turno->completo())
                                <div class="alert alert-warning mt-3 mb-0">
                                    <i class="fas fa-clock mr-1"></i>
                                    Te falta registrar la salida de esta jornada.
                                </div>
                            @endunless

                            <div class="row mt-3 no-gutters">
                                <div class="col-md-6 border-md-right">
                                    <div class="px-2 text-center">
                                        <label>Foto de entrada</label>
                                        <div>
                                            @if ($turno->foto_entrada)
                                                <img src="{{ route('asistencias.foto.publica', [$turno->id, 'entrada']) }}"
                                                    alt="Foto de entrada" class="foto-turno img-thumbnail">
                                            @else
                                                <span class="text-muted">Sin foto</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="px-2 text-center">
                                        <label>Foto de salida</label>
                                        <div>
                                            @if ($turno->foto_salida)
                                                <img src="{{ route('asistencias.foto.publica', [$turno->id, 'salida']) }}"
                                                    alt="Foto de salida" class="foto-turno img-thumbnail">
                                            @else
                                                <span class="text-muted">Sin foto</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            @endif
        </div>
    </div>
@endsection
