@extends('layouts.admin')
@section('content')
    <h1>Crear Nuevo Usuario</h1>

    <div class="card card-outline card-success">
        <div class="card-header">
            <h3 class="card-title">Formulario de Registro</h3>
        </div>
        <div class="card-body">
            <form action="{{ route('usuarios.store') }}" method="POST">
                @csrf
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
                        value="{{ old('name') }}">
                </div>

                <div class="form-group">
                    <label>Correo Electrónico</label>
                    <div class="input-group mb-3">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                        </div>
                        <input type="email" class="form-control" name="email" placeholder="correo@ejemplo.com" required
                            value="{{ old('email') }}">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Contraseña</label>
                            <input type="password" class="form-control" name="password" placeholder="Mínimo 8 caracteres"
                                required autocomplete="new-password">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Confirmar Contraseña</label>
                            <input type="password" class="form-control" name="password_confirmation"
                                placeholder="Repita la contraseña" required autocomplete="new-password">
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label>Rol</label>
                    <select class="form-control" name="rol" required>
                        @foreach ($roles as $role)
                            <option value="{{ $role->name }}" {{ old('rol', 'subadmin') == $role->name ? 'selected' : '' }}>
                                {{ ucfirst($role->name) }}
                            </option>
                        @endforeach
                    </select>
                    <small class="text-muted">Por defecto se asigna <strong>Subadmin</strong>. Puedes cambiarlo o
                        ajustar sus permisos después desde el botón de llaves en la lista de usuarios.</small>
                </div>

                <div class="mt-3">
                    <a href="{{ route('usuarios.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left mr-1"></i> Volver
                    </a>
                    <button type="submit" class="btn btn-success">
                        Guardar Usuario <i class="fas fa-save mr-1"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection