@extends('layouts.admin')
@section('content')
    <h1>Editar Tutor</h1>

    <div class="card card-outline card-warning">
        <div class="card-header">
            <h3 class="card-title">Formulario de Edición</h3>
        </div>
        <div class="card-body">

            <form action="{{ route('tutores.update', $tutor->id) }}" method="POST" enctype="multipart/form-data">
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

                        {{-- Código y Carrera Asignada --}}
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Código Docente</label>
                                    <input type="text" class="form-control" name="codigo_docente"
                                        placeholder="Ej: JOAL" value="{{ old('codigo_docente', $tutor->codigo_docente) }}" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Carrera Asignada</label>
                                    <select class="form-control" name="carrera_id" required>
                                        <option value="">Seleccione una carrera</option>
                                        @foreach ($carreras as $carrera)
                                            <option value="{{ $carrera->id }}" {{ old('carrera_id', $tutor->carrera_id) == $carrera->id ? 'selected' : '' }}>
                                                {{ $carrera->nombre }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>

                        {{-- Nombres y Apellidos --}}
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Nombres</label>
                                    <input type="text" class="form-control" name="nombres"
                                        placeholder="Nombres completos" value="{{ old('nombres', $tutor->nombres) }}" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Apellidos</label>
                                    <input type="text" class="form-control" name="apellidos"
                                        placeholder="Apellidos completos" value="{{ old('apellidos', $tutor->apellidos) }}" required>
                                </div>
                            </div>
                        </div>

                        {{-- Correo --}}
                        <div class="form-group">
                            <label>Correo</label>
                            <div class="input-group mb-3">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                </div>
                                <input type="email" class="form-control" name="correo"
                                    placeholder="correo@ejemplo.com" value="{{ old('correo', $tutor->correo) }}" required>
                            </div>
                        </div>

                        {{-- Estado --}}
                        <div class="form-group">
                            <label>Estado</label>
                            <select class="form-control" name="estado" required>
                                <option value="1" {{ $tutor->estado == '1' ? 'selected' : '' }}>Activo</option>
                                <option value="0" {{ $tutor->estado != '1' ? 'selected' : '' }}>Inactivo</option>
                            </select>
                        </div>

                        {{-- Botones --}}
                        <div class="mt-3">
                            <a href="{{ route('tutores.index') }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-left mr-1"></i> Volver
                            </a>
                            <button type="submit" class="btn btn-warning">Actualizar Tutor
                                <i class="fas fa-save mr-1"></i>
                            </button>
                        </div>

                    </div>

                    {{-- Columna derecha: solo foto --}}
                    <div class="col-md-4 text-center">
                        <label>Foto del Tutor</label>
                        <div>
                            @if ($tutor->foto)
                                <img id="preview" src="{{ route('tutores.foto', $tutor) }}" alt="Foto"
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

    {{-- Script para previsualizar la foto --}}
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
    </script>
@endsection
