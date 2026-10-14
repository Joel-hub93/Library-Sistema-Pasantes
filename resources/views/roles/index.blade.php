@extends('layouts.admin')
@section('content')
    <h1>Gestión de Roles</h1>

    @if (session('confirmacion'))
        @include('partials.toast', [
            'icono' => 'success',
            'titulo' => '¡Éxito!',
            'mensaje' => session('confirmacion'),
        ])
    @endif

    @if (session('error'))
        @include('partials.toast', [
            'icono' => 'error',
            'titulo' => 'Error',
            'mensaje' => session('error'),
        ])
    @endif

    <div class="card card-outline card-primary">
        <div class="card-header">
            <h3 class="card-title">Roles Registrados</h3>
            <div class="card-tools">
                @can('crear-roles')
                    <a href="{{ route('roles.create') }}" class="btn btn-success btn-sm">
                        <i class="fas fa-plus mr-1"></i> Nuevo Rol
                    </a>
                @endcan
            </div>
        </div>
        <div class="card-body">
            <table id="example1" class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre del Rol</th>
                        <th>Permisos Asignados</th>
                        <th class="text-center">Estado</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($roles as $role)
                        @php
                            $esDelSistema = in_array($role->name, $rolesSistema);
                            $usuariosAsignados = $role->users_count;
                            $noSePuedeEliminar = $esDelSistema || $usuariosAsignados > 0;
                        @endphp
                        <tr>
                            <td>{{ $role->id }}</td>
                            <td><strong>{{ ucfirst($role->name) }}</strong></td>
                            <td>
                                @if ($role->permissions->count() > 0)
                                    <span class="badge badge-info">{{ $role->permissions->count() }} permisos</span>
                                @else
                                    <span class="badge badge-secondary">Sin permisos</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if ($esDelSistema)
                                    <span class="badge badge-primary">Del sistema</span>
                                @elseif ($usuariosAsignados > 0)
                                    <span class="badge badge-warning">{{ $usuariosAsignados }} usuario(s)</span>
                                @else
                                    <span class="badge badge-light border">Sin asignar</span>
                                @endif
                            </td>
                            <td class="text-center text-nowrap">
                                @can('editar-roles')
                                    <a href="{{ route('roles.edit', $role->id) }}" class="btn btn-warning btn-sm btn-accion"
                                        title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                @endcan
                                @can('eliminar-roles')
                                    @if ($noSePuedeEliminar)
                                        <button type="button" class="btn btn-secondary btn-sm btn-accion" disabled
                                            title="{{ $esDelSistema ? 'Rol del sistema: no se puede eliminar' : 'Tiene ' . $usuariosAsignados . ' usuario(s) asignado(s)' }}">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    @else
                                        <form action="{{ route('roles.destroy', $role->id) }}" method="POST"
                                            class="d-inline form-eliminar"
                                            data-confirm-title="¿Eliminar este rol?">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger btn-sm btn-accion" title="Eliminar">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    @endif
                                @endcan
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <script>
        $(function() {
            $('#example1').DataTable({
                "language": {
                    "url": "//cdn.datatables.net/plug-ins/1.10.25/i18n/Spanish.json"
                },
                "responsive": true,
                "autoWidth": false,
            });
        });
    </script>
@endsection