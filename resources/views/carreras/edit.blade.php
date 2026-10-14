@extends('layouts.admin')
@section('content')
    <h1>Editar Carrera</h1>

    <div class="card card-outline card-warning">
        <div class="card-header">
            <h3 class="card-title">Formulario de Edición</h3>
        </div>
        <div class="card-body">

            <form action="{{ route('carreras.update', $carrera->id) }}" method="POST">
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

                    {{-- Código y Horas --}}
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Código de Carrera</label>
                            <input type="text" class="form-control" name="codigo_carrera"
                                placeholder="Ej: TINF-001"
                                value="{{ old('codigo_carrera', $carrera->codigo_carrera) }}" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Horas de Prácticas</label>
                            <input type="number" class="form-control" name="horas_practicas" min="0"
                                placeholder="Ej: 480"
                                value="{{ old('horas_practicas', $carrera->horas_practicas) }}" required>
                        </div>
                    </div>
                </div>

                {{-- Nombre --}}
                <div class="form-group">
                    <label>Nombre de la Carrera</label>
                    <input type="text" class="form-control" name="nombre"
                        value="{{ old('nombre', $carrera->nombre) }}" required>
                </div>

                {{-- Estado --}}
                <div class="form-group">
                    <label>Estado</label>
                    <select class="form-control" name="estado" required>
                        <option value="1" {{ $carrera->estado == '1' ? 'selected' : '' }}>Activo</option>
                        <option value="0" {{ $carrera->estado != '1' ? 'selected' : '' }}>Inactivo</option>
                    </select>
                </div>

                {{-- Botones --}}
                <div class="mt-3">
                    <a href="{{ route('carreras.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left mr-1"></i> Volver
                    </a>
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-save mr-1"></i> Actualizar Carrera 
                    </button>
                </div>

            </form>
        </div>
    </div>
@endsection
