@extends('layouts.admin')
@section('content')
    <h1>Detalle del Pasante</h1>

    <div class="card card-outline card-primary">
        <div class="card-header">
            <h3 class="card-title">Información Completa</h3>
        </div>
        <div class="card-body">
            <div class="row">

                {{-- Columna izquierda: foto --}}
                <div class="col-md-4 text-center">
                    @if ($pasante->foto)
                        <img src="{{ route('pasantes.foto', $pasante) }}" alt="Foto de {{ $pasante->nombres }}"
                            class="img-fluid img-thumbnail mb-2" style="max-height: 250px;">
                    @else
                        <img src="{{ asset('img/Avatar-Default-Img.jpg') }}" alt="Sin Foto"
                            class="img-fluid img-thumbnail mb-2" style="max-height: 250px;">
                    @endif
                    <h4 class="mb-0">{{ $pasante->nombres }} {{ $pasante->apellidos }}</h4>
                    <small class="text-muted">C.I. {{ $pasante->cedula }}</small>
                    <br>
                    <br>
                     @can('editar-pasantes')
                        <a href="{{ route('pasantes.edit', $pasante->id) }}" class="btn btn-primary">
                            <i class="fas fa-edit mr-1"></i> Editar
                        </a>
                    @endcan
                </div>

                {{-- Columna derecha: datos --}}
                <div class="col-md-8">
                    <table class="table table-striped table-bordered">
                        <tr>
                            <th style="width: 35%"><i class="fas fa-id-card mr-2"></i>Cedula</th>
                            <td>{{ $pasante->cedula }}</td>
                        </tr>
                        <tr>
                            <th><i class="fas fa-user mr-2"></i>Nombres</th>
                            <td>{{ $pasante->nombres }}</td>
                        </tr>
                        <tr>
                            <th><i class="fas fa-users mr-2"></i>Apellidos</th>
                            <td>{{ $pasante->apellidos }}</td>
                        </tr>
                        <tr>
                            <th><i class="fas fa-envelope mr-2"></i>Email</th>
                            <td>{{ $pasante->email }}</td>
                        </tr>
                        <tr>
                            <th><i class="fas fa-graduation-cap mr-2"></i>Carrera</th>
                            <td>{{ $pasante->carrera->nombre }}</td>
                        </tr>
                        <tr>
                            <th><i class="fas fa-clock mr-2"></i>Horas de Practica</th>
                            <td>{{ $pasante->carrera->horas_practicas }} horas</td>
                        </tr>
                        <tr>
                            <th><i class="fas fa-chalkboard-teacher mr-2"></i>Tutor</th>
                            <td>
                                {{ $pasante->tutor->nombres }} {{ $pasante->tutor->apellidos }}
                                <small class="text-muted">({{ $pasante->tutor->codigo_docente }})</small>
                            </td>
                        </tr>
                        <tr>
                            <th><i class="fas fa-phone mr-2"></i>Telefono</th>
                            <td>{{ $pasante->telefono }}</td>
                        </tr>
                        <tr>
                            <th><i class="fas fa-calendar-alt mr-2"></i>Fecha de Ingreso</th>
                            <td>{{ $pasante->fecha_ingreso->format('d/m/Y') }}</td>
                        </tr>
                        <tr>
                            <th><i class="fas fa-toggle-on mr-2"></i>Estado</th>
                            <td>
                                @if ($pasante->estado == '1')
                                    <span class="badge badge-success">Activo</span>
                                @else
                                    <span class="badge badge-danger">Inactivo</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th><i class="fas fa-clock mr-2"></i>Registrado el</th>
                            <td>{{ $pasante->created_at->format('d/m/Y H:i') }}</td>
                        </tr>
                    </table>
                </div>

            </div>

            {{-- Botones --}}
            <div class="mt-3">
                <a href="{{ route('pasantes.index') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left mr-1"></i> Volver
                </a>
            </div>
        </div>
    </div>
@endsection
