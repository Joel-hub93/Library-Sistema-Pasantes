@extends('layouts.admin')
@section('content')
    <h1>Lista de Carreras</h1>

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
            <h3 class="card-title">Carreras Registradas</h3>
            <div class="card-tools">
                @can('crear-carreras')
                    <a href="{{ route('carreras.create') }}" class="btn btn-success btn-sm">
                        <i class="fas fa-graduation-cap mr-1"></i> Nueva Carrera
                    </a>
                @endcan
            </div>
        </div>
        <div class="card-body">
            <table id="example1" class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Código Carrera</th>
                        <th>Nombre</th>
                        <th>Horas de Prácticas</th>
                        <th>Estado</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($carreras as $carrera)
                        <tr>
                            <td>{{ $carrera->id }}</td>
                            <td>{{ $carrera->codigo_carrera }}</td>
                            <td>{{ $carrera->nombre }}</td>
                            <td>{{ $carrera->horas_practicas }}</td>
                            <td class="text-center text-nowrap">
                                @if ($carrera->estado == '1')
                                    <span class="badge badge-success badge-estado">Activo</span>
                                @else
                                    <span class="badge badge-danger badge-estado">Inactivo</span>
                                @endif
                            </td>
                            <td class="text-center text-nowrap">
                                <a href="{{ route('carreras.show', $carrera->id) }}" class="btn btn-info btn-sm btn-accion" title="Ver">
                                    <i class="fas fa-eye"></i>
                                </a>
                                @can('editar-carreras')
                                    <a href="{{ route('carreras.edit', $carrera->id) }}" class="btn btn-warning btn-sm btn-accion" title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                @endcan
                                @can('eliminar-carreras')
                                    <form action="{{ route('carreras.destroy', $carrera->id) }}" method="POST"
                                        class="d-inline form-eliminar"
                                        data-confirm-title="¿Eliminar esta carrera?">
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
            $("#example1").DataTable({
                "responsive": true,
                "lengthChange": true,
                "autoWidth": false,
                "language": {
                    "emptyTable": "No hay carreras registradas",
                    "info": "Mostrando _START_ a _END_ de _TOTAL_ carreras",
                    "infoEmpty": "Mostrando 0 a 0 de 0 carreras",
                    "infoFiltered": "(Filtrado de _MAX_ total carreras)",
                    "search": "Buscar:",
                    "lengthMenu": "Mostrar _MENU_ carreras",
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
                "pageLength": 10,
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
