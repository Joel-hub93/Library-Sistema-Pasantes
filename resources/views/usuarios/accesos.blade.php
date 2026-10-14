@extends('layouts.admin')
@section('content')
    <h1>Roles y Permisos</h1>

    @if (session('confirmacion'))
        @include('partials.toast', [
            'icono' => 'success',
            'titulo' => '¡Éxito!',
            'mensaje' => session('confirmacion'),
        ])
    @endif

    @if ($errors->any())
        @include('partials.toast', [
            'icono' => 'warning',
            'titulo' => 'Revisa los datos',
            'mensaje' => $errors->first(),
        ])
    @endif

    {{-- Identidad del usuario: solo lectura, no se modifica desde aqui --}}
    <div class="card card-outline card-secondary">
        <div class="card-body d-flex justify-content-between align-items-center">
            <div>
                <h4 class="mb-1">{{ $user->name }}</h4>
                <p class="mb-0 text-muted">
                    <i class="fas fa-envelope mr-1"></i>{{ $user->email }}
                </p>
            </div>
            <div class="text-right">
                <small class="d-block text-muted">Estado de la cuenta</small>
                @if ($user->estado == '1')
                    <span class="badge badge-success">Activo</span>
                @else
                    <span class="badge badge-danger">Inactivo</span>
                @endif
                <small class="d-block text-muted mt-2">Roles actuales</small>
                @forelse ($user->roles as $rol)
                    <span class="badge badge-primary">{{ ucfirst($rol->name) }}</span>
                @empty
                    <span class="badge badge-secondary">Sin rol</span>
                @endforelse
            </div>
        </div>
    </div>

    <form action="{{ route('usuarios.accesos.update', $user->id) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="row">
            <div class="col-md-4">

                {{-- Roles: hasta 2, marcados con checkbox --}}
                <div class="card card-outline card-primary">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-user-shield mr-1"></i> Roles</h3>
                    </div>
                    <div class="card-body">
                        @foreach ($roles as $role)
                            <div class="custom-control custom-checkbox mb-2">
                                <input type="checkbox" class="custom-control-input" id="rol-{{ $role->id }}"
                                    name="roles[]" value="{{ $role->name }}" data-nombre="{{ $role->name }}"
                                    {{ in_array($role->name, old('roles', $userRoles)) ? 'checked' : '' }}>
                                <label class="custom-control-label" for="rol-{{ $role->id }}">
                                    {{ ucfirst($role->name) }}
                                </label>
                            </div>
                        @endforeach

                        <small class="text-muted d-block mt-2">
                            Podeis asignar hasta <strong>2 roles</strong> por usuario.
                        </small>
                        <div id="aviso-roles" class="alert alert-warning mt-2 mb-0 p-2 d-none" style="font-size: .85rem;">
                            <i class="fas fa-exclamation-triangle mr-1"></i>
                            Solo puede tener 2 roles. Desmarque uno para continuar.
                        </div>
                    </div>
                </div>

                <div class="d-flex flex-column mb-3">
                    <button type="submit" class="btn btn-warning mb-2">
                        <i class="fas fa-save mr-1"></i> Guardar Accesos
                    </button>
                    <a href="{{ route('usuarios.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left mr-1"></i> Volver
                    </a>
                </div>

            </div>

            {{-- Permisos directos --}}
            <div class="col-md-8">
                <div class="card card-outline card-primary">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-key mr-1"></i> Permisos Directos</h3>
                        <div class="card-tools">
                            <span class="badge badge-light border">
                                <span id="contador-directos">0</span> directos
                            </span>
                        </div>
                    </div>
                    <div class="card-body" style="max-height: 560px; overflow-y: auto;">

                        <div class="alert alert-info py-2" style="font-size: .85rem;">
                            <i class="fas fa-info-circle mr-1"></i>
                            <strong>Heredado del rol:</strong> este permiso ya viene incluido en
                            @if (count($userRoles) > 0)
                                <strong>{{ implode(', ', array_map('ucfirst', $userRoles)) }}</strong>
                            @else
                                el rol del usuario
                            @endif
                            y no se puede quitar aqui. Para dejarlo de tener, edita el rol.
                            <br>
                            <strong>Directo:</strong> permiso que se le concede solo a este usuario.
                            Marquelo o desmarquelo libremente.
                        </div>

                        @foreach ($permissionsGrouped as $resource => $permissions)
                            @php
                                $heredadosDelGrupo = collect($permissions)
                                    ->filter(fn($p) => in_array($p->name, $permissionsViaRoles))
                                    ->count();
                            @endphp
                            <div class="mb-3">
                                <h6 class="text-uppercase font-weight-bold text-primary border-bottom pb-1 mb-2">
                                    {{ ucfirst($resource) }}
                                    @if ($heredadosDelGrupo > 0)
                                        <small class="text-muted float-right font-weight-normal">
                                            {{ $heredadosDelGrupo }} heredados
                                        </small>
                                    @endif
                                </h6>
                                <div class="row">
                                    @foreach ($permissions as $permission)
                                        @php
                                            $isInherited = in_array($permission->name, $permissionsViaRoles);
                                            $isDirect = in_array($permission->name, $userPermissionsDirect);
                                            $isChecked = in_array(
                                                $permission->name,
                                                old('direct_permissions', $userPermissionsDirect),
                                            );
                                        @endphp
                                        <div class="col-md-6">
                                            <div class="custom-control custom-checkbox mb-1">
                                                <input type="checkbox" class="custom-control-input permiso-directo"
                                                    id="perm-u-{{ $permission->id }}" value="{{ $permission->name }}"
                                                    name="direct_permissions[]" {{ $isChecked ? 'checked' : '' }}
                                                    {{ $isInherited ? 'disabled' : '' }}>
                                                <label class="custom-control-label" for="perm-u-{{ $permission->id }}">
                                                    {{ $permission->name }}
                                                    @if ($isInherited)
                                                        <span class="badge badge-secondary ml-1" style="font-size: .65rem;">
                                                            heredado
                                                        </span>
                                                    @endif
                                                </label>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach

                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection

@push('scripts')
    <script>
        (function() {
            var MAX_ROLES = 2;
            var aviso = document.getElementById('aviso-roles');
            var checksRoles = Array.prototype.slice.call(
                document.querySelectorAll('input[name="roles[]"]')
            );

            function validarRoles() {
                var marcados = checksRoles.filter(function(c) {
                    return c.checked;
                }).length;

                checksRoles.forEach(function(c) {
                    c.disabled = (marcados >= MAX_ROLES && !c.checked);
                });

                aviso.classList.toggle('d-none', marcados < MAX_ROLES);
            }

            checksRoles.forEach(function(c) {
                c.addEventListener('change', validarRoles);
            });

            validarRoles();

            var contador = document.getElementById('contador-directos');

            function contarDirectos() {
                var directos = document.querySelectorAll('.permiso-directo:checked').length;
                contador.textContent = directos;
            }

            var permisos = Array.prototype.slice.call(document.querySelectorAll('.permiso-directo'));
            permisos.forEach(function(c) {
                c.addEventListener('change', contarDirectos);
            });

            contarDirectos();
        })();
    </script>
@endpush
