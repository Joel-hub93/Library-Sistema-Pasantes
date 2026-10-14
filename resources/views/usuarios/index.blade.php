@extends('layouts.admin')
@section('content')
    <h1>Lista de Usuarios</h1>

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

    <div class="card card-outline card-primary">
        <div class="card-header">
            <h3 class="card-title">Usuarios Registrados</h3>
            <div class="card-tools">
                @can('crear-usuarios')
                    <a href="{{ route('usuarios.create') }}" class="btn btn-success btn-sm">
                        <i class="fas fa-user-plus mr-1"></i> Nuevo Usuario
                    </a>
                @endcan
            </div>
        </div>
        <div class="card-body">
            <table id="example1" class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Correo Electrónico</th>
                        <th class="text-center">Roles</th>
                        <th>Estado</th>
                        <th>Fecha de Registro</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($usuarios as $user)
                        <tr>
                            <td>{{ $user->id }}</td>
                            <td>{{ $user->name }}</td>
                            <td>{{ $user->email }}</td>
                            <td class="text-center text-nowrap">
                                @forelse ($user->roles as $rol)
                                    <span class="badge badge-primary badge-estado">{{ ucfirst($rol->name) }}</span>
                                @empty
                                    <span class="badge badge-secondary badge-estado">Sin rol</span>
                                @endforelse
                            </td>
                            <td class="text-center">
                                @if ($user->estado == '1')
                                    <span class="badge badge-success badge-estado">Activo</span>
                                @else
                                    <span class="badge badge-danger badge-estado">Inactivo</span>
                                @endif
                            </td>
                            <td>{{ $user->created_at->format('d/m/Y H:i') }}</td>
                            <td class="text-center text-nowrap">
                                <a href="{{ route('usuarios.show', $user->id) }}" class="btn btn-info btn-sm btn-accion" title="Ver">
                                    <i class="fas fa-eye"></i>
                                </a>
                                @can('editar-usuarios')
                                    <a href="{{ route('usuarios.edit', $user->id) }}" class="btn btn-warning btn-sm btn-accion" title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                @endcan
                                @can('asignar-permisos-a-usuario')
                                    <a href="{{ route('usuarios.accesos', $user->id) }}" class="btn btn-secondary btn-sm btn-accion" title="Roles y Permisos">
                                        <i class="fas fa-key"></i>
                                    </a>
                                @endcan
                                @can('eliminar-usuarios')
                                    <form action="{{ route('usuarios.destroy', $user->id) }}" method="POST" class="d-inline form-eliminar" data-confirm-title="¿Eliminar este usuario?">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-sm btn-accion" title="Eliminar">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                @endcan
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <style>
        #example1 .badge-estado {
            font-size: .9rem;
            padding: .45em .65em;
        }

        #example1 .btn-accion {
            padding: .18rem .45rem;
            font-size: .8rem;
            line-height: 1.4;
        }

        #example1 .btn-accion+.btn-accion {
            margin-left: .15rem;
        }
    </style>

    <script>
        $(function() {
            $('#example1').DataTable({
                "language": {
                    "emptyTable": "No hay usuarios registrados",
                    "info": "Mostrando _START_ a _END_ de _TOTAL_ usuarios",
                    "infoEmpty": "Mostrando 0 a 0 de 0 usuarios",
                    "infoFiltered": "(Filtrado de _MAX_ total usuarios)",
                    "search": "Buscar:",
                    "lengthMenu": "Mostrar _MENU_ usuarios",
                    "loadingRecords": "Cargando...",
                    "processing": "Procesando...",
                    "zeroRecords": "Sin resultados encontrados",
                    "paginate": {
                        "first": "Primero",
                        "last": "Último",
                        "next": "Siguiente",
                        "previous": "Anterior"
                    }
                },
                "responsive": true,
                "lengthChange": true,
                "autoWidth": false
            });
        });
    </script>
@endsection