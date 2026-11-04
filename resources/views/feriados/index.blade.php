@extends('layouts.admin')
@section('content')

    <h1>Feriados</h1>

    @if (session('confirmacion'))
        @include('partials.toast', [
            'icono' => 'success',
            'titulo' => '¡Listo!',
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

    @can('crear-feriados')
        <div class="card card-outline card-success">
            <div class="card-header">
                <h3 class="card-title">Agregar feriado</h3>
            </div>
            <div class="card-body">
                <form action="{{ route('feriados.store') }}" method="POST">
                    @csrf

                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Fecha <span class="text-danger">*</span></label>
                                <input type="date" name="fecha" class="form-control" required
                                    value="{{ old('fecha') }}">
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Nombre (opcional)</label>
                                <input type="text" name="nombre" class="form-control" maxlength="120"
                                    placeholder="Ej: Día de los Difuntos" value="{{ old('nombre') }}">
                            </div>
                        </div>

                        <div class="col-md-2 d-flex align-items-end">
                            <div class="form-group w-100">
                                <button type="submit" class="btn btn-success btn-block">
                                    <i class="fas fa-plus mr-1"></i> Agregar
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    @endcan

    <div class="card card-outline card-primary">
        <div class="card-header">
            <h3 class="card-title">Feriados registrados</h3>
        </div>
        <div class="card-body">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Día</th>
                        <th>Nombre</th>
                        @can('eliminar-feriados')
                            <th class="text-center">Acciones</th>
                        @endcan
                    </tr>
                </thead>
                <tbody>
                    @forelse ($feriados as $feriado)
                        <tr>
                            <td>{{ $feriado->fecha->format('d/m/Y') }}</td>
                            <td class="text-capitalize">{{ $feriado->fecha->translatedFormat('l') }}</td>
                            <td>{{ $feriado->nombre ?? '—' }}</td>

                            @can('eliminar-feriados')
                                <td class="text-center">
                                    <form action="{{ route('feriados.destroy', $feriado) }}" method="POST"
                                        class="d-inline form-eliminar"
                                        data-confirm-title="¿Eliminar este feriado?"
                                        data-confirm-text="El día volverá a contar como hábil.">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-sm" title="Eliminar">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            @endcan
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ auth()->user()->can('eliminar-feriados') ? 4 : 3 }}"
                                class="text-center text-muted">
                                No hay feriados registrados.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection