@extends('layouts.admin')
@section('content')
    <h1>Lista de Asistencias</h1>

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

    {{-- Filtros: se envían por GET y el listado se recarga con los resultados --}}
    <div class="card card-outline card-primary">
        <div class="card-header">
            <h3 class="card-title">Filtro</h3>

            <div class="card-tools">
                @can('crear-pasantes')
                    <a href="{{ route('asistencias.create') }}" class="btn btn-success btn-sm">
                        <i class="fas fa-plus mr-1"></i> Nueva Asistencia
                    </a>
                @endcan
            </div>
        </div>
        <div class="card-body">
            <form action="{{ route('asistencias.index') }}" method="GET">
                <div class="row align-items-end">
                    <div class="col-md-3">
                        <div class="form-group mb-2">
                            <label>Pasante</label>
                            <select class="form-control" name="pasante_id" id="filtro_pasante">
                                <option value="">Todos los pasantes</option>
                                @foreach ($pasantes as $pasante)
                                    <option value="{{ $pasante->id }}" data-carrera="{{ $pasante->carrera?->nombre }}"
                                        {{ ($filtros['pasante_id'] ?? '') == $pasante->id ? 'selected' : '' }}>
                                        {{ $pasante->apellidos }} {{ $pasante->nombres }} ({{ $pasante->cedula }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-5">
                        <div class="form-group mb-2">
                            <label>Carrera</label>
                            <input type="text" class="form-control" id="filtro_carrera" placeholder="Se completa solo"
                                readonly tabindex="-1">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group mb-2">
                            <label>Fecha</label>
                            <input type="date" class="form-control" name="fecha" value="{{ $filtros['fecha'] ?? '' }}">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary mb-2">
                            <i class="fas fa-search mr-1"></i> Filtrar
                        </button>
                        <a href="{{ route('asistencias.index') }}" class="btn btn-secondary mb-2">
                            <i class="fas fa-eraser mr-1"></i> Limpiar
                        </a>
                    </div>
                </div>
            </form>

            <script>
                (function() {
                    var selectPasante = document.getElementById('filtro_pasante');
                    var carrera = document.getElementById('filtro_carrera');

                    function sincronizar() {
                        var opcion = selectPasante.options[selectPasante.selectedIndex];
                        carrera.value = opcion && opcion.dataset.carrera ? opcion.dataset.carrera : '';
                    }

                    selectPasante.addEventListener('change', sincronizar);
                    sincronizar();
                })();
            </script>
        </div>
    </div>

    <div class="card card-outline card-primary">
        <div class="card-body">
            <table id="example1" class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Pasante</th>
                        <th>Fecha</th>
                        <th>Jornada</th>
                        <th class="text-center">Entrada</th>
                        <th class="text-center">Salida</th>
                        <th class="text-center">Total</th>
                        <th class="text-center">Extra</th>
                        <th class="text-center">Fotos</th>
                        <th class="text-center">Estado</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($asistencias as $asistencia)
                        @php
                            $clave = $asistencia->pasante_id.'|'.$asistencia->fecha->toDateString();
                            $apoyoDia = $apoyoPorDia[$clave] ?? 0;
                        @endphp
                        <tr class="{{ in_array($clave, $diasCompartidos) ? 'fila-dia-compartido' : '' }}">
                            <td>{{ $asistencia->id }}</td>
                            <td>
                                {{ $asistencia->pasante->apellidos }} {{ $asistencia->pasante->nombres }}
                                <small class="text-muted d-block">
                                    {{ $asistencia->pasante->cedula }} |
                                    <span title="{{ $asistencia->pasante->carrera?->nombre }}">
                                        {{ $asistencia->pasante->carrera?->codigo_carrera }}
                                    </span>
                                </small>
                            </td>
                            <td>{{ $asistencia->fecha->format('d/m/Y') }}</td>
                            <td>{{ $jornadas[$asistencia->jornada] ?? $asistencia->jornada }}</td>
                            <td class="text-center">{{ $asistencia->hora_entrada }}</td>
                            <td class="text-center">{{ $asistencia->hora_salida ?? '—' }}</td>
                            <td class="text-center text-nowrap">{{ $asistencia->totalHoras() }}</td>
                            <td class="text-center text-nowrap">
                                @if ($apoyoDia > 0)
                                    {{ gmdate('H:i', $apoyoDia * 60) }}
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-center text-nowrap">
                                @if ($asistencia->foto_entrada)
                                    <a href="{{ route('asistencias.foto', [$asistencia->id, 'entrada']) }}" target="_blank"
                                        class="btn btn-info btn-sm btn-accion" title="Foto de entrada">
                                        <i class="fas fa-image"></i>
                                    </a>
                                @endif
                                @if ($asistencia->foto_salida)
                                    <a href="{{ route('asistencias.foto', [$asistencia->id, 'salida']) }}" target="_blank"
                                        class="btn btn-info btn-sm btn-accion" title="Foto de salida">
                                        <i class="fas fa-image"></i>
                                    </a>
                                @endif
                                @if (!$asistencia->foto_entrada && !$asistencia->foto_salida)
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if ($asistencia->completo())
                                    <span class="badge badge-success badge-estado">Completo</span>
                                @else
                                    <span class="badge badge-warning badge-estado">Sin salida</span>
                                @endif
                            </td>
                            <td class="text-center text-nowrap">
                                <a href="{{ route('asistencias.show', $asistencia->id) }}"
                                    class="btn btn-info btn-sm btn-accion" title="Ver">
                                    <i class="fas fa-eye"></i>
                                </a>
                                @can('editar-pasantes')
                                    <a href="{{ route('asistencias.edit', $asistencia->id) }}"
                                        class="btn btn-warning btn-sm btn-accion" title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                @endcan
                                @can('eliminar-pasantes')
                                    <form action="{{ route('asistencias.destroy', $asistencia->id) }}" method="POST"
                                        class="d-inline form-eliminar" data-confirm-title="¿Eliminar esta asistencia?">
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
                "order": [],
                "language": {
                    "emptyTable": "No hay asistencias registradas",
                    "info": "Mostrando _START_ a _END_ de _TOTAL_ asistencias",
                    "infoEmpty": "Mostrando 0 a 0 de 0 asistencias",
                    "infoFiltered": "(Filtrado de _MAX_ total asistencias)",
                    "search": "Buscar:",
                    "lengthMenu": "Mostrar _MENU_ asistencias",
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
            });
        });
    </script>

    <style>
        #example1 .btn-accion {
            padding: .18rem .45rem;
            font-size: .8rem;
            line-height: 1.4;
        }

        #example1 .btn-accion+.btn-accion {
            margin-left: .15rem;
        }

        #example1 .badge-estado {
            font-size: .9rem;
            padding: .45em .65em;
        }

        /* Los dias con mas de un turno se pintan igual en el listado admin y en
           el historial del pasante, para que el "Extra" (apoyo del dia) se vea
           como dato del dia y no del turno. */
        #example1 .fila-dia-compartido > td {
            background-color: #fff9c4 !important;
        }
    </style>
@endsection
