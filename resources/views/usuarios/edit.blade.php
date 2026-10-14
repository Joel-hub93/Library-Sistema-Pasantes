@extends('layouts.admin')
@section('content')
    <h1>Editar Usuario</h1>

    <div class="card card-outline card-warning">
        <div class="card-header">
            <h3 class="card-title">Formulario de Edición</h3>
        </div>
        <div class="card-body">
            <form action="{{ route('usuarios.update', $user->id) }}" method="POST">
                @csrf
                @method('PUT')
                @if ($errors->any())
                    @include('partials.toast', [
                        'icono' => 'warning',
                        'titulo' => 'Revisa los datos',
                        'mensaje' => $errors->first(),
                    ])
                @endif

                <div class="form-group">
                    <label>Nombre Completo</label>
                    <input type="text" class="form-control" name="name" placeholder="Nombre y apellidos" required
                        value="{{ old('name', $user->name) }}">
                </div>

                <div class="form-group">
                    <label>Correo Electrónico</label>
                    <div class="input-group mb-3">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                        </div>
                        <input type="email" class="form-control" name="email" placeholder="correo@ejemplo.com" required
                            value="{{ old('email', $user->email) }}">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Contraseña <small class="text-muted">(Déjala vacía para no cambiarla)</small></label>
                            <input type="password" class="form-control" name="password" placeholder="Mínimo 8 caracteres"
                                autocomplete="new-password">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Confirmar Contraseña</label>
                            <input type="password" class="form-control" name="password_confirmation"
                                placeholder="Repita la contraseña" autocomplete="new-password">
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label>Estado</label>
                    <select class="form-control" name="estado" required>
                        <option value="1" {{ old('estado', $user->estado) == '1' ? 'selected' : '' }}>Activo</option>
                        <option value="0" {{ old('estado', $user->estado) != '1' ? 'selected' : '' }}>Inactivo</option>
                    </select>
                </div>

                <div class="mt-3">
                    <a href="{{ route('usuarios.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left mr-1"></i> Volver
                    </a>
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-save mr-1"></i> Actualizar Usuario
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection