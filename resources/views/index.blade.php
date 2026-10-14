@extends('layouts.admin')
@section('content')
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Dashboard</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('inicio') }}">Inicio</a></li>
                        <li class="breadcrumb-item active">Dashboard</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <div class="card card-outline card-primary">
        <div class="card-body d-flex justify-content-between align-items-center">
            <div>
                <h4 class="mb-1">Sistema de Control de Asistencia</h4>
                <p class="mb-0 text-muted">Gestión integral de pasantes</p>
            </div>
            <div class="text-right ml-auto">
                <small class="d-block text-muted">Fecha</small>
                <span class="h5 text-dark mb-0">{{ ucfirst($fechaHoy) }}</span>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-3 col-6">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3>{{ $totalPasantes }}</h3>
                    <p>Pasantes</p>
                </div>
                <div class="icon">
                    <i class="fas fa-users"></i>
                </div>
                <a href="{{ route('pasantes.index') }}" class="small-box-footer">
                    Ver más <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>

        <div class="col-lg-3 col-6">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3>{{ $totalCarreras }}</h3>
                    <p>Carreras</p>
                </div>
                <div class="icon">
                    <i class="fas fa-graduation-cap"></i>
                </div>
                <a href="{{ route('carreras.index') }}" class="small-box-footer">
                    Ver más <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>

        <div class="col-lg-3 col-6">
            <div class="small-box bg-primary">
                <div class="inner">
                    <h3>{{ $totalTutores }}</h3>
                    <p>Tutores</p>
                </div>
                <div class="icon">
                    <i class="fas fa-chalkboard-teacher"></i>
                </div>
                <a href="{{ route('tutores.index') }}" class="small-box-footer">
                    Ver más <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>

        <div class="col-lg-3 col-6">
            <div class="small-box bg-secondary">
                <div class="inner">
                    <h3>{{ $totalUsuarios }}</h3>
                    <p>Usuarios</p>
                </div>
                <div class="icon">
                    <i class="fas fa-user"></i>
                </div>
                <a href="{{ route('usuarios.index') }}" class="small-box-footer">
                    Ver más <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>
    </div>
@endsection