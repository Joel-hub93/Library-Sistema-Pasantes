@extends('layouts.admin')
@section('content')
    <h1>Gestión de Permisos</h1>

    <div class="card card-outline card-primary">
        <div class="card-header">
            <h3 class="card-title">Listado de Permisos</h3>
        </div>
        <div class="card-body">
            @foreach ($permissionsGrouped as $resource => $permissions)
                <div class="card card-outline card-secondary mb-3">
                    <div class="card-header">
                        <h5 class="card-title text-uppercase font-weight-bold text-primary mb-0">
                            {{ ucfirst($resource) }}
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            @foreach ($permissions as $permission)
                                <div class="col-md-4 col-sm-6 mb-2">
                                    <span class="badge badge-light border p-2" style="font-size: 0.85rem;">
                                        <i class="fas fa-key mr-1 text-muted"></i>
                                        {{ $permission->name }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endsection