@extends('layouts.admin')
@section('content')
    <h1>Detalle del Usuario</h1>

    @if (session('confirmacion'))
        @include('partials.toast', [
            'icono' => 'success',
            'titulo' => '¡Éxito!',
            'mensaje' => session('confirmacion'),
        ])
    @endif

    <div class="card card-outline card-primary">
        <div class="card-header">
            <h3 class="card-title">Información Completa</h3>
        </div>
        <div class="card-body">
            <table class="table table-striped table-bordered">
                <tr>
                    <th style="width: 35%"><i class="fas fa-id-card mr-2"></i>ID</th>
                    <td>{{ $user->id }}</td>
                </tr>
                <tr>
                    <th><i class="fas fa-user mr-2"></i>Nombre Completo</th>
                    <td>{{ $user->name }}</td>
                </tr>
                <tr>
                    <th><i class="fas fa-envelope mr-2"></i>Correo Electrónico</th>
                    <td>{{ $user->email }}</td>
                </tr>
                <tr>
                    <th><i class="fas fa-toggle-on mr-2"></i>Estado</th>
                    <td>
                        @if ($user->estado == '1')
                            <span class="badge badge-success">Activo</span>
                        @else
                            <span class="badge badge-danger">Inactivo</span>
                        @endif
                    </td>
                </tr>
                <tr>
                    <th><i class="fas fa-user-shield mr-2"></i>Roles</th>
                    <td>
                        @forelse ($user->roles as $rol)
                            <span class="badge badge-primary">{{ ucfirst($rol->name) }}</span>
                        @empty
                            <span class="badge badge-secondary">Sin rol</span>
                        @endforelse
                    </td>
                </tr>
                <tr>
                    <th><i class="fas fa-clock mr-2"></i>Registrado el</th>
                    <td>{{ $user->created_at->format('d/m/Y H:i') }}</td>
                </tr>
                @if ($user->updated_at)
                    <tr>
                        <th><i class="fas fa-clock mr-2"></i>Última Actualización</th>
                        <td>{{ $user->updated_at->format('d/m/Y H:i') }}</td>
                    </tr>
                @endif
            </table>

            <div class="mt-3">
                <a href="{{ route('usuarios.index') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left mr-1"></i> Volver
                </a>
                @can('editar-usuarios')
                    <a href="{{ route('usuarios.edit', $user->id) }}" class="btn btn-primary">
                        <i class="fas fa-edit mr-1"></i> Editar
                    </a>
                @endcan

            </div>
        </div>
    </div>
@endsection
