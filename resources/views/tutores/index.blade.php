@extends('layouts.admin')
@section('content')
    <h1>Lista de Tutores</h1>

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
            'titulo' => 'No se pudo eliminar',
            'mensaje' => session('error'),
        ])
    @endif

    <div class="card card-outline card-primary">
        <div class="card-header">
            <h3 class="card-title">Tutores Registrados</h3>
            <div class="card-tools">
                @can('crear-tutores')
                    <a href="{{ route('tutores.create') }}" class="btn btn-success btn-sm">
                        <i class="fas fa-chalkboard-teacher mr-1"></i> Nuevo Tutor
                    </a>
                @endcan
            </div>
        </div>
        <div class="card-body">
            <table id="example1" class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Código Docente</th>
                        <th>Nombres</th>
                        <th>Apellidos</th>
                        <th>Carrera Asignada</th>
                        <th>Estado</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($tutores as $tutor)
                        <tr>
                            <td>{{ $tutor->id }}</td>
                            <td>{{ $tutor->codigo_docente }}</td>
                            <td>{{ $tutor->nombres }}</td>
                            <td>{{ $tutor->apellidos }}</td>
                            <td>{{ $tutor->carrera?->nombre ?? '—' }}</td>
                            <td class="text-center">
                                @if ($tutor->estado == '1')
                                    <span class="badge badge-success badge-estado">Activo</span>
                                @else
                                    <span class="badge badge-danger badge-estado">Inactivo</span>
                                @endif
                            </td>
                            <td class="text-center text-nowrap">
                                <a href="{{ route('tutores.show', $tutor->id) }}" class="btn btn-info btn-sm btn-accion"
                                    title="Ver">
                                    <i class="fas fa-eye"></i>
                                </a>
@can('editar-tutores')
                                    <a href="{{ route('tutores.edit', $tutor->id) }}" class="btn btn-warning btn-sm btn-accion"
                                        title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                @endcan
                                @can('eliminar-tutores')
                                    <form action="{{ route('tutores.destroy', $tutor->id) }}" method="POST"
                                        class="d-inline form-eliminar"
                                        data-confirm-title="¿Eliminar este tutor?"
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
                    "emptyTable": "No hay tutores registrados",
                    "info": "Mostrando _START_ a _END_ de _TOTAL_ tutores",
                    "infoEmpty": "Mostrando 0 a 0 de 0 tutores",
                    "infoFiltered": "(Filtrado de _MAX_ total tutores)",
                    "search": "Buscar:",
                    "lengthMenu": "Mostrar _MENU_ tutores",
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
@endsection
