@extends('reportes.pdf.layout')

@section('titulo', 'Listado de Pasantes')
@section('orientacion', 'landscape')
@section('encabezado', 'achatado')

@section('contenido')
    <h1>Listado de Pasantes</h1>

    <table>
        <thead>
            <tr>
                <th style="width: 5%">ID</th>
                <th>Cédula</th>
                <th>Nombres</th>
                <th>Apellidos</th>
                <th>Carrera</th>
                <th>Tutor</th>
                <th>Horas a realizar</th>
                <th>Fecha de ingreso</th>
                <th style="width: 9%">Estado</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($pasantes as $pasante)
                <tr>
                    <td>{{ $pasante->id }}</td>
                    <td>{{ $pasante->cedula }}</td>
                    <td>{{ $pasante->nombres }}</td>
                    <td>{{ $pasante->apellidos }}</td>
                    <td>{{ $pasante->carrera?->nombre ?? '—' }}</td>
                    <td>{{ $pasante->tutor ? trim($pasante->tutor->apellidos.' '.$pasante->tutor->nombres) : '—' }}</td>
                    <td>{{ $pasante->horasRequeridas() }} h</td>
                    <td>{{ $pasante->fecha_ingreso?->format('d/m/Y') ?? '—' }}</td>
                    <td>{{ $pasante->estado == '1' ? 'Activo' : 'Inactivo' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endsection
