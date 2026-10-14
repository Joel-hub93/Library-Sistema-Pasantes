@extends('reportes.pdf.layout')

@section('titulo', 'Listado de Usuarios')
@section('orientacion', 'landscape')
@section('encabezado', 'achatado')

@section('contenido')
    <h1>Listado de Usuarios</h1>

    <table>
        <thead>
            <tr>
                <th style="width: 6%">ID</th>
                <th>Nombre</th>
                <th>Correo Electrónico</th>
                <th>Roles</th>
                <th style="width: 10%">Estado</th>
                <th>Fecha de Registro</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($usuarios as $usuario)
                <tr>
                    <td>{{ $usuario->id }}</td>
                    <td>{{ $usuario->name }}</td>
                    <td>{{ $usuario->email }}</td>
                    <td>{{ $usuario->roles->pluck('name')->map(fn ($rol) => ucfirst($rol))->implode(', ') ?: 'Sin rol' }}</td>
                    <td>{{ $usuario->estado == '1' ? 'Activo' : 'Inactivo' }}</td>
                    <td>{{ $usuario->created_at->format('d/m/Y H:i') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endsection
