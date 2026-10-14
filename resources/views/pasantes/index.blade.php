@extends('layouts.admin')
@section('content')
    <h1>Lista de Pasantes</h1>

    @if (session('confirmacion'))
        @include('partials.toast', [
            'icono' => 'success',
            'titulo' => '¡Éxito!',
            'mensaje' => session('confirmacion'),
        ])
    @endif

    <div class="card card-outline card-primary">
        <div class="card-header">
            <h3 class="card-title">Pasantes Registrados</h3>
            <div class="card-tools">
                @can('crear-pasantes')
                    <a href="{{ route('pasantes.create') }}" class="btn btn-success btn-sm">
                        <i class="fas fa-user-plus mr-1"></i> Nuevo Pasante
                    </a>
                @endcan
            </div>

        </div>
        <div class="card-body">
            <table id="example1" class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Cedula</th>
                        <th>Nombres</th>
                        <th>Apellidos</th>
                        <th>Carrera</th>
                        <th>Tutor</th>
                        <th class="text-center">Horas</th>
                        <th>Fecha Ingreso</th>
                        <th>Estado</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($pasantes as $pasante)
                        <tr>
                            <td>{{ $pasante->id }}</td>
                            <td>{{ $pasante->cedula }}</td>
                            <td>{{ $pasante->nombres }}</td>
                            <td>{{ $pasante->apellidos }}</td>
                            <td title="{{ $pasante->carrera->nombre }}">{{ $pasante->carrera->codigo_carrera }}</td>
                            <td title="{{ $pasante->tutor->nombres }} {{ $pasante->tutor->apellidos }}">{{ $pasante->tutor->codigo_docente }}</td>
                            <td class="text-center">{{ $pasante->carrera->horas_practicas }}</td>
                            <td>{{ $pasante->fecha_ingreso->format('d/m/Y') }}</td>
                            <td class="text-center">
                                @if ($pasante->estado == '1')
                                    <span class="badge badge-success badge-estado">Activo</span>
                                @else
                                    <span class="badge badge-danger badge-estado">Inactivo</span>
                                @endif
                            </td>
                            <td class="text-center text-nowrap">
                                <a href="{{ route('pasantes.show', $pasante->id) }}" class="btn btn-info btn-sm btn-accion" title="Ver">
                                    <i class="fas fa-eye"></i>
                                </a>
                                @can('editar-pasantes')
                                    <a href="{{ route('pasantes.edit', $pasante->id) }}" class="btn btn-warning btn-sm btn-accion" title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                @endcan
                                @can('eliminar-pasantes')
                                    <form action="{{ route('pasantes.destroy', $pasante->id) }}" method="POST"
                                        class="d-inline form-eliminar"
                                        data-confirm-title="¿Eliminar este pasante?"
                                        data-confirm-text="Esta acción no se puede deshacer. También se borrará su foto.">
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

    <script>
        $(function() {
            $('#example1').DataTable({
                "language": {
                    "emptyTable": "No hay pasantes registrados",
                    "info": "Mostrando _START_ a _END_ de _TOTAL_ pasantes",
                    "infoEmpty": "Mostrando 0 a 0 de 0 pasantes",
                    "infoFiltered": "(Filtrado de _MAX_ total pasantes)",
                    "search": "Buscar:",
                    "lengthMenu": "Mostrar _MENU_ pasantes",
                    "loadingRecords": "Cargando...",
                    "processing": "Procesando...",
                    "zeroRecords": "Sin resultados encontrados",
                    "paginate": {
                        "first": "Primero",
                        "last": "Ultimo",
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
    <style>
        #example1 .btn-accion {
            padding: .18rem .45rem;
            font-size: .8rem;
            line-height: 1.4;
        }
        #example1 .btn-accion + .btn-accion {
            margin-left: .15rem;
        }
        #example1 .badge-estado {
            font-size: .9rem;
            padding: .45em .65em;
        }
    </style>
@endsection
