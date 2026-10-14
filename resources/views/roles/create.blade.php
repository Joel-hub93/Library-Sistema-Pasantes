@extends('layouts.admin')
@section('content')
    <h1>Crear Nuevo Rol</h1>

    <div class="card card-outline card-success">
        <div class="card-header">
            <h3 class="card-title">Formulario de Registro</h3>
        </div>
        <div class="card-body">
            <form action="{{ route('roles.store') }}" method="POST">
                @csrf

                @if ($errors->any())
                    @include('partials.toast', [
                        'icono' => 'warning',
                        'titulo' => 'Revisa los datos',
                        'mensaje' => $errors->first(),
                    ])
                @endif

                <div class="form-group">
                    <label>Nombre del Rol</label>
                    <input type="text" class="form-control" name="name" placeholder="Ej: subadmin"
                        value="{{ old('name') }}" required>
                    <small class="text-muted">Se guardará en minúsculas automáticamente.</small>
                </div>

                <div class="form-group">
                    <label>Permisos Asignados</label>
                    <div class="card card-outline card-secondary">
                        <div class="card-body" style="max-height: 500px; overflow-y: auto;">
                            @foreach ($permissionsGrouped as $resource => $permissions)
                                <h6 class="text-uppercase font-weight-bold text-primary mt-3 mb-2">
                                    {{ ucfirst($resource) }}
                                </h6>
                                <div class="row">
                                    @foreach ($permissions as $permission)
                                        <div class="col-md-4 col-sm-6">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="permissions[]"
                                                    id="perm-{{ $permission->id }}" value="{{ $permission->name }}"
                                                    {{ in_array($permission->name, old('permissions', [])) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="perm-{{ $permission->id }}">
                                                    {{ $permission->name }}
                                                </label>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                                <hr>
                            @endforeach
                        </div>
                    </div>
                    <small class="text-muted">Puedes seleccionar todos los permisos necesarios para este rol.</small>
                </div>

                <div class="mt-3">
                    <a href="{{ route('roles.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left mr-1"></i> Volver
                    </a>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save mr-1"></i> Guardar Rol
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection