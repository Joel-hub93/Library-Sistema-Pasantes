@extends('layouts.admin')

@push('estilos')
    <style>
        .historial .table th,
        .historial .table td {
            font-size: .9rem;
            vertical-align: middle;
        }

        .historial .reloj-foto {
            max-height: 70px;
        }

        .historial .col-foto {
            width: 100px;
        }

        /* Las filas de un dia con varias jornadas se pintan igual para
           dejar claro que el apoyo mostrado es el total del dia. */
        .historial .fila-dia-compartido > td {
            background-color: #fff9c4 !important;
        }
    </style>
@endpush

@section('content')
    <h1>Historial de Asistencias</h1>

    <div class="card card-outline card-primary historial">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h3 class="card-title">
                    <i class="fas fa-history mr-1"></i>
                    Todas los Resgistros de Asistencia
                </h3>

            </div>
        </div>

        <div class="card-body">

            {{-- Mismo patron de avisos que el resto del proyecto --}}
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

            {{-- Dias habiles que el pasante no registro --}}
            @if ($diasSinRegistro > 0)
                <div class="alert alert-warning">
                    <i class="fas fa-bell mr-1"></i>
                    Tienes <strong>{{ $diasSinRegistro }}</strong>
                    {{ $diasSinRegistro === 1 ? 'día hábil' : 'días hábiles' }}
                    sin registro desde tu fecha de ingreso.
                    <strong>Notifica al administrador</strong> para que registre esos días;
                    no se pueden recuperar desde aquí.
                </div>
            @endif

            @if ($asistencias->isEmpty())
                <div class="alert alert-info">
                    <i class="fas fa-inbox mr-1"></i>
                    Todavía no tienes asistencias registradas.
                </div>
            @else
                <div class="table-responsive">
                    <table id="tablaHistorial" class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Jornada</th>
                                <th class="text-center">Entrada</th>
                                <th class="text-center">Salida</th>
                                <th class="text-center">Total</th>
                                <th class="text-center">Apoyo</th>
                                <th class="text-center col-foto">Foto entrada</th>
                                <th class="text-center col-foto">Foto salida</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($asistencias as $turno)
                                <tr
                                    class="{{ in_array($turno->fecha->toDateString(), $diasCompartidos) ? 'fila-dia-compartido' : '' }}">
                                    <td class="text-nowrap">
                                        {{ $turno->fecha->format('d/m/Y') }}
                                        <small class="text-muted d-block text-capitalize">
                                            {{ $turno->fecha->translatedFormat('l') }}
                                        </small>
                                    </td>
                                    <td>{{ $jornadas[$turno->jornada] ?? $turno->jornada }}</td>
                                    <td class="text-center">{{ $turno->hora_entrada }}</td>
                                    <td class="text-center">
                                        @if ($turno->hora_salida)
                                            {{ $turno->hora_salida }}
                                        @else
                                            <span class="badge badge-warning">Sin salida</span>
                                        @endif
                                    </td>
                                    <td class="text-center text-nowrap">
                                        {{ $turno->totalHoras() }}
                                    </td>
                                    <td class="text-center text-nowrap">
                                        @php($apoyoDia = $apoyoPorDia[$turno->fecha->toDateString()] ?? 0)
                                        @if ($apoyoDia > 0)
                                            {{ gmdate('H:i', $apoyoDia * 60) }}
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td class="text-center col-foto">
                                        @if ($turno->foto_entrada)
                                            <img src="{{ route('asistencias.foto.publica', [$turno->id, 'entrada']) }}"
                                                alt="Foto de entrada" class="reloj-foto img-thumbnail">
                                        @else
                                            <span class="text-muted" title="Sin foto de entrada">—</span>
                                        @endif
                                    </td>
                                    <td class="text-center col-foto">
                                        @if ($turno->foto_salida)
                                            <img src="{{ route('asistencias.foto.publica', [$turno->id, 'salida']) }}"
                                                alt="Foto de salida" class="reloj-foto img-thumbnail">
                                        @else
                                            <span class="text-muted" title="Sin foto de salida">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <p class="text-muted small">
                    Periodo: {{ $pasante->fecha_ingreso?->format('d/m/Y') ?? 'sin fecha' }}
                    a {{ now()->format('d/m/Y') }}.
                </p>
            @endif
        </div>
    </div>

    <script>
        $(function() {
            // Los scripts de DataTables ya los carga el layout admin
            $('#tablaHistorial').DataTable({
                "responsive": true,
                "autoWidth": false,
                "lengthChange": true,
                "lengthMenu": [10, 25, 50, 100],
                // El controlador ya entrega las filas de hoy hacia atras
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
@endsection
