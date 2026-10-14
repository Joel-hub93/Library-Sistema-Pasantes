@extends('layouts.admin')
@section('content')
    <h1>Detalle de la Carrera</h1>

    <div class="card card-outline card-info">
        <div class="card-header">
            <h3 class="card-title">Información de la Carrera</h3>

        </div>
        <div class="card-body">

            <table class="table table-bordered table-striped">
                <tr>
                    <th style="width: 35%"><i class="fas fa-barcode mr-2"></i>Código de Carrera</th>
                    <td>{{ $carrera->codigo_carrera }}</td>
                </tr>
                <tr>
                    <th><i class="fas fa-graduation-cap mr-2"></i>Nombre</th>
                    <td>{{ $carrera->nombre }}</td>
                </tr>
                <tr>
                    <th><i class="fas fa-clock mr-2"></i>Horas de Prácticas</th>
                    <td>{{ $carrera->horas_practicas }}</td>
                </tr>
                <tr>
                    <th><i class="fas fa-toggle-on mr-2"></i>Estado</th>
                    <td>
                        @if ($carrera->estado == '1')
                            <span class="badge badge-success">Activo</span>
                        @else
                            <span class="badge badge-danger">Inactivo</span>
                        @endif
                    </td>
                </tr>
                <tr>
                    <th><i class="fas fa-clock mr-2"></i>Registrado el</th>
                    <td>{{ $carrera->created_at->format('d/m/Y H:i') }}</td>
                </tr>
            </table>

            <div class="mt-3">
                <a href="{{ route('carreras.index') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left mr-1"></i> Volver
                </a>
                @can('editar-carreras')
                    <a href="{{ route('carreras.edit', $carrera->id) }}" class="btn btn-info">
                        <i class="fas fa-edit mr-1"></i> Editar
                    </a>
                @endcan
            </div>
        </div>
    </div>
@endsection
