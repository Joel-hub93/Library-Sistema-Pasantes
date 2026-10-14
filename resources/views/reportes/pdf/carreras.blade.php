@extends('reportes.pdf.layout')

@section('titulo', 'Listado de Carreras')
@section('orientacion', 'landscape')
@section('encabezado', 'achatado')

@section('contenido')
    <h1>Listado de Carreras</h1>

    <table>
        <thead>
            <tr>
                <th style="width: 8%">ID</th>
                <th>Código Carrera</th>
                <th>Nombre Carrera</th>
                <th>Horas de Prácticas</th>
                <th style="width: 12%">Estado</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($carreras as $carrera)
                <tr>
                    <td>{{ $carrera->id }}</td>
                    <td>{{ $carrera->codigo_carrera }}</td>
                    <td>{{ $carrera->nombre }}</td>
                    <td>{{ $carrera->horas_practicas }} h</td>
                    <td>{{ $carrera->estado == '1' ? 'Activa' : 'Inactiva' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endsection
