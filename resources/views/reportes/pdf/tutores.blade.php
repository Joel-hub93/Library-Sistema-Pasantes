@extends('reportes.pdf.layout')

@section('titulo', 'Listado de Tutores')
@section('orientacion', 'landscape')
@section('encabezado', 'achatado')

@section('contenido')
    <h1>Listado de Tutores</h1>

    <table>
        <thead>
            <tr>
                <th style="width: 8%">ID</th>
                <th>Código Docente</th>
                <th>Nombres</th>
                <th>Apellidos</th>
                <th>Carrera Asignada</th>
                <th style="width: 12%">Estado</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($tutores as $tutor)
                <tr>
                    <td>{{ $tutor->id }}</td>
                    <td>{{ $tutor->codigo_docente }}</td>
                    <td>{{ $tutor->nombres }}</td>
                    <td>{{ $tutor->apellidos }}</td>
                    <td>{{ $tutor->carrera?->nombre ?? '—' }}</td>
                    <td>{{ $tutor->estado == '1' ? 'Activo' : 'Inactivo' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endsection
