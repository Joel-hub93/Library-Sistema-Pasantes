@extends('layouts.admin')
@section('content')
    <h1>Detalle del Tutor</h1>

    <div class="card card-outline card-primary">
        <div class="card-header">
            <h3 class="card-title">Información Completa</h3>
        </div>
        <div class="card-body">
            <div class="row">

                {{-- Columna izquierda: foto --}}
                <div class="col-md-4 text-center">
                    @if ($tutor->foto)
                        <img src="{{ route('tutores.foto', $tutor) }}" alt="Foto de {{ $tutor->nombres }}"
                            class="img-fluid img-thumbnail mb-2" style="max-height: 250px;">
                    @else
                        <img src="{{ asset('img/Avatar-Default-Img.jpg') }}" alt="Sin Foto"
                            class="img-fluid img-thumbnail mb-2" style="max-height: 250px;">
                    @endif
                    <h4 class="mb-0">{{ $tutor->nombres }} {{ $tutor->apellidos }}</h4>
                    <small class="text-muted">Código Docente: {{ $tutor->codigo_docente }}</small>
                    <br>
                    <br>
                    @can('editar-tutores')
                    <a href="{{ route('tutores.edit', $tutor->id) }}" class="btn btn-primary">
                        <i class="fas fa-edit mr-1"></i> Editar
                    </a>
                @endcan
                </div>

                {{-- Columna derecha: datos --}}
                <div class="col-md-8">
                    <table class="table table-striped table-bordered">
                        <tr>
                            <th style="width: 35%"><i class="fas fa-id-card mr-2"></i>Código Docente</th>
                            <td>{{ $tutor->codigo_docente }}</td>
                        </tr>
                        <tr>
                            <th><i class="fas fa-user mr-2"></i>Nombres</th>
                            <td>{{ $tutor->nombres }}</td>
                        </tr>
                        <tr>
                            <th><i class="fas fa-users mr-2"></i>Apellidos</th>
                            <td>{{ $tutor->apellidos }}</td>
                        </tr>
                        <tr>
                            <th><i class="fas fa-graduation-cap mr-2"></i>Carrera Asignada</th>
                            <td>{{ $tutor->carrera?->nombre ?? '—' }}</td>
                        </tr>
                        <tr>
                            <th><i class="fas fa-envelope mr-2"></i>Correo</th>
                            <td>{{ $tutor->correo }}</td>
                        </tr>
                        <tr>
                            <th><i class="fas fa-toggle-on mr-2"></i>Estado</th>
                            <td>
                                @if ($tutor->estado == '1')
                                    <span class="badge badge-success">Activo</span>
                                @else
                                    <span class="badge badge-danger">Inactivo</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th><i class="fas fa-clock mr-2"></i>Registrado el</th>
                            <td>{{ $tutor->created_at->format('d/m/Y H:i') }}</td>
                        </tr>
                    </table>
                </div>

            </div>

            {{-- Botones --}}
            <div class="mt-3">
                <a href="{{ route('tutores.index') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left mr-1"></i> Volver
                </a>
            </div>
        </div>
    </div>
@endsection
