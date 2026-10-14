@extends('layouts.admin')
@section('content')
    <h1>Crear Nueva Carrera</h1>

    <div class="card card-outline card-success">
        <div class="card-header">
            <h3 class="card-title">Formulario de Registro</h3>
        </div>
        <div class="card-body">

            <form action="{{ route('carreras.store') }}" method="POST">
                @csrf
                @if ($errors->any())
                    @include('partials.toast', [
                        'icono' => 'warning',
                        'titulo' => 'Revisa los datos',
                        'mensaje' => $errors->first(),
                    ])
                @endif
                {{-- Nombre de la carrera --}}
                <div class="form-group">
                    <label>Nombre de la Carrera</label>
                    <input type="text" class="form-control" name="nombre"
                        placeholder="Nombre completo de la carrera" required value="{{ old('nombre') }}">
                </div>

                <div class="row">

                    {{-- Código de carrera --}}
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Código de Carrera</label>
                            <input type="text" class="form-control" name="codigo_carrera"
                                placeholder="Ej: TINF-001" required value="{{ old('codigo_carrera') }}">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Horas de Prácticas</label>
                            <input type="number" class="form-control" name="horas_practicas" min="0"
                                placeholder="Ej: 480" required value="{{ old('horas_practicas') }}">
                        </div>
                    </div>
                </div>

                {{-- Botones --}}
                <div class="mt-3">
                    <a href="{{ route('carreras.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left mr-1"></i> Volver
                    </a>
                    <button type="submit" class="btn btn-success">
                        Guardar Carrera <i class="fas fa-save mr-1"></i>
                    </button>
                </div>

            </form>
        </div>
    </div>
@endsection
