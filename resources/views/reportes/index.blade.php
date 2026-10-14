@extends('layouts.admin')

@section('content')
    <h1>Listado de Reportes</h1>

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
            'titulo' => 'No se pudo completar',
            'mensaje' => session('error'),
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
            <h3 class="card-title">
                <i class="fas fa-file-pdf mr-1"></i> Reportes
            </h3>
        </div>
        <div class="card-body">
            <table id="tablaReportes" class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Módulo</th>
                        <th>Listado</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><i class="fas fa-user mr-1"></i> Gestión de Usuarios</td>
                        <td>Listado de Usuarios</td>
                        <td class="text-center text-nowrap">
                            <a href="{{ route('reportes.usuarios.pdf') }}" class="btn btn-danger btn-sm btn-accion"
                                title="Descargar listado de usuarios en PDF">
                                <i class="fas fa-file-pdf"></i>
                            </a>
                            <a href="{{ route('reportes.usuarios.excel') }}" class="btn btn-success btn-sm btn-accion"
                                title="Descargar listado de usuarios en Excel">
                                <i class="fas fa-file-excel"></i>
                            </a>
                        </td>
                    </tr>
                    <tr>
                        <td><i class="fas fa-users mr-1"></i> Gestión de Pasantes</td>
                        <td>Listado de Pasantes</td>
                        <td class="text-center text-nowrap">
                            <a href="{{ route('reportes.pasantes.pdf') }}" class="btn btn-danger btn-sm btn-accion"
                                title="Descargar listado de pasantes en PDF">
                                <i class="fas fa-file-pdf"></i>
                            </a>
                            <a href="{{ route('reportes.pasantes.excel') }}" class="btn btn-success btn-sm btn-accion"
                                title="Descargar listado de pasantes en Excel">
                                <i class="fas fa-file-excel"></i>
                            </a>
                        </td>
                    </tr>
                    <tr>
                        <td><i class="fas fa-graduation-cap mr-1"></i> Gestión de Carreras</td>
                        <td>Listado de Carreras</td>
                        <td class="text-center text-nowrap">
                            <a href="{{ route('reportes.carreras.pdf') }}" class="btn btn-danger btn-sm btn-accion"
                                title="Descargar listado de carreras en PDF">
                                <i class="fas fa-file-pdf"></i>
                            </a>
                            <a href="{{ route('reportes.carreras.excel') }}" class="btn btn-success btn-sm btn-accion"
                                title="Descargar listado de carreras en Excel">
                                <i class="fas fa-file-excel"></i>
                            </a>
                        </td>
                    </tr>
                    <tr>
                        <td><i class="fas fa-chalkboard-teacher mr-1"></i> Gestión de Tutores</td>
                        <td>Listado de Tutores</td>
                        <td class="text-center text-nowrap">
                            <a href="{{ route('reportes.tutores.pdf') }}" class="btn btn-danger btn-sm btn-accion"
                                title="Descargar listado de tutores en PDF">
                                <i class="fas fa-file-pdf"></i>
                            </a>
                            <a href="{{ route('reportes.tutores.excel') }}" class="btn btn-success btn-sm btn-accion"
                                title="Descargar listado de tutores en Excel">
                                <i class="fas fa-file-excel"></i>
                            </a>
                        </td>
                    </tr>
                    <tr>
                        <td><i class="fas fa-clock mr-1"></i> Control de Asistencias</td>
                        <td>Bitácoras | Informes Finales de Asistencias</td>
                        <td class="text-center text-nowrap">
                            <button type="button" class="btn btn-danger btn-sm btn-accion"
                                title="Bitácora o informe final en PDF" data-toggle="modal"
                                data-target="#modalPdfAsistencias">
                                <i class="fas fa-file-pdf"></i>
                            </button>
                            <button type="button" class="btn btn-success btn-sm btn-accion"
                                title="Bitácora de asistencias en Excel" data-toggle="modal"
                                data-target="#modalExcelAsistencias">
                                <i class="fas fa-file-excel"></i>
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>

            <p class="text-muted small mb-0 mt-3">
                El <strong>informe final</strong> es el único reporte que se guarda en el sistema; una vez generado
                cierra el periodo de registro del pasante. Los demás listados y bitácoras se descargan directamente
                al equipo, sin dejar rastro.
            </p>
        </div>
    </div>

    {{-- Modal PDF de asistencias: bitacora o informe final --}}
    <div class="modal fade" id="modalPdfAsistencias" tabindex="-1" role="dialog" aria-hidden="true"
        data-pasantes="{{ $pasantes->toJson() }}"
        data-url-preview="{{ route('reportes.informe.preview') }}"
        data-url-generar="{{ route('reportes.generar') }}">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-file-pdf mr-1"></i> Asistencias — PDF</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Tipo de reporte</label>
                        <select class="form-control" id="tipoReportePdf">
                            <option value="bitacora">Bitácora PDF</option>
                            <option value="informe">Informe Final</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Escoja el pasante</label>
                        <select class="form-control" id="selectPasantePdf">
                            <option value="">Seleccione un pasante</option>
                        </select>
                    </div>

                    {{-- Bitacora: rango de fechas (desde no anterior a la fecha de ingreso) --}}
                    <div id="contenidoBitacoraPdf">
                        <form id="formBitacoraPdf" method="GET" action="{{ route('reportes.asistencias.pdf') }}">
                            <input type="hidden" name="tipo" value="bitacora">
                            <input type="hidden" name="pasante_id" id="hiddenPasantePdf">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group mb-2">
                                        <label for="desdePdf">Desde</label>
                                        <input type="date" class="form-control" id="desdePdf" name="desde">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group mb-2">
                                        <label for="hastaPdf">Hasta</label>
                                        <input type="date" class="form-control" id="hastaPdf" name="hasta">
                                    </div>
                                </div>
                            </div>
                            <small class="text-muted">
                                La fecha "desde" no puede ser anterior a la fecha de ingreso del pasante.
                            </small>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                                <button type="submit" class="btn btn-danger">
                                    <i class="fas fa-file-pdf mr-1"></i> Descargar
                                </button>
                            </div>
                        </form>
                    </div>

                    {{-- Informe final: vista previa / generar, o descargar / reabrir --}}
                    <div id="contenidoInformePdf" style="display: none;">
                        <div class="alert alert-info py-2" id="infoInformePdf">
                            Seleccione un pasante para ver el estado de su informe final.
                        </div>

                        <div id="grupoInformePendiente">
                            <div class="row">
                                <div class="col-md-5">
                                    <a href="#" id="btnVistaPrevia" target="_blank"
                                        class="btn btn-info btn-block">
                                        <i class="fas fa-eye mr-1"></i> Vista previa
                                    </a>
                                </div>
                                <div class="col-md-7">
                                    <form id="formGenerarInforme" method="POST"
                                        action="{{ route('reportes.generar') }}">
                                        @csrf
                                        <input type="hidden" name="pasante_id" id="hiddenPasanteInforme">
                                        <button type="submit" class="btn btn-success btn-block">
                                            <i class="fas fa-file-signature mr-1"></i> Generar informe final
                                        </button>
                                    </form>
                                </div>
                            </div>
                            <small class="text-muted d-block mt-2">
                                La vista previa descarga cómo quedaría el documento con los datos actuales, sin
                                guardarlo. El informe solo se genera cuando el pasante ya cumple las horas de
                                práctica de su carrera.
                            </small>
                        </div>

                        <div id="grupoInformeGenerado" style="display: none;">
                            <div class="row">
                                <div class="col-md-5">
                                    <a href="#" id="btnDescargarInforme" target="_blank"
                                        class="btn btn-info btn-block">
                                        <i class="fas fa-file-pdf mr-1"></i> Descargar informe
                                    </a>
                                </div>
                                <div class="col-md-7">
                                    <form id="formAnularInforme" method="POST" action="#">
                                        @csrf
                                        <button type="submit" class="btn btn-warning btn-block">
                                            <i class="fas fa-lock-open mr-1"></i> Reabrir informe
                                        </button>
                                    </form>
                                </div>
                            </div>
                            <small class="text-muted d-block mt-2">
                                Este pasante ya tiene su informe final generado y su periodo de registro está
                                cerrado. Si fue un error, puede reabrirlo: el pasante volverá a poder registrar
                                y la opción de generar volverá a estar disponible.
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Excel de la bitacora de asistencias --}}
    <div class="modal fade" id="modalExcelAsistencias" tabindex="-1" role="dialog" aria-hidden="true"
        data-pasantes="{{ $pasantes->toJson() }}">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-file-excel mr-1"></i> Asistencias — Excel (Bitácora)</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="formExcelAsistencias" method="GET" action="{{ route('reportes.asistencias.excel') }}">
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Escoja el pasante</label>
                            <select class="form-control" name="pasante_id" id="selectPasanteExcel">
                                <option value="">Seleccione un pasante</option>
                            </select>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-2">
                                    <label for="desdeExcel">Desde</label>
                                    <input type="date" class="form-control" id="desdeExcel" name="desde">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mb-2">
                                    <label for="hastaExcel">Hasta</label>
                                    <input type="date" class="form-control" id="hastaExcel" name="hasta">
                                </div>
                            </div>
                        </div>
                        <small class="text-muted">
                            La fecha "desde" no puede ser anterior a la fecha de ingreso del pasante.
                        </small>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-file-excel mr-1"></i> Descargar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('js/reportes.js') }}"></script>

    <style>
        #tablaReportes .btn-accion {
            padding: .42rem .7rem;
            font-size: .95rem;
            line-height: 1.5;
        }
        #tablaReportes .btn-accion + .btn-accion {
            margin-left: .2rem;
        }
    </style>
@endpush