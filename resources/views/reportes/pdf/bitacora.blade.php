@extends('reportes.pdf.layout')

@section('titulo', 'Bitácora de registros — '.$pasante->cedula)
@section('orientacion', 'landscape')
@section('encabezado', 'achatado')

@section('estilos')
    body {
        font-size: 10pt;
    }

    h1 {
        margin: 0 0 4pt 0;
    }

    table {
        margin-bottom: 10pt;
    }

    th {
        text-align: center;
    }

    .datos-pasante th {
        width: 24%;
        text-align: left;
        background-color: #f2f2f2;
    }

    .datos-pasante td {
        text-align: left;
    }

    .bitacora td {
        text-align: center;
    }

    .bitacora .sin-registro {
        background-color: #fdecea;
        color: #a94442;
    }

    .bitacora .sin-salida {
        background-color: #fff3cd;
        color: #856404;
    }

    .bitacora .completo {
        background-color: #d9ead3;
        color: #38761d;
    }

    .fila-totales td {
        background-color: #dce6f1;
        font-weight: bold;
    }
@endsection

@section('contenido')
    <h1>BITÁCORA DE REGISTROS DE ASISTENCIA</h1>

    {{-- Datos del pasante --}}
    <table class="datos-pasante">
        <tr>
            <th>Cédula</th>
            <td>{{ $pasante->cedula }}</td>
            <th>Apellidos y Nombres</th>
            <td>{{ $pasante->apellidos }} {{ $pasante->nombres }}</td>
        </tr>
        <tr>
            <th>Carrera</th>
            <td>{{ $pasante->carrera?->nombre ?? '—' }}</td>
            <th>Tutor Asignado</th>
            <td>{{ $pasante->tutor ? trim($pasante->tutor->apellidos.' '.$pasante->tutor->nombres) : '—' }}</td>
        </tr>
        <tr>
            <th>Horas de Prácticas</th>
            <td>{{ $pasante->horasRequeridas() ? $pasante->horasRequeridas().' h' : '—' }}</td>
            <th>Fecha de ingreso</th>
            <td>{{ $pasante->fecha_ingreso?->format('d/m/Y') ?? '—' }}</td>
        </tr>
    </table>

    <table class="bitacora">
        <thead>
            <tr>
                <th style="width: 13%">Fecha</th>
                <th style="width: 13%">Jornada/s</th>
                <th style="width: 15%">Ingreso/s</th>
                <th style="width: 15%">Salida/s</th>
                <th style="width: 12%">Horas Completas</th>
                <th style="width: 13%">Tiempo Adicional</th>
                <th style="width: 19%">Estado</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($filas as $fila)
                <tr>
                    <td>
                        {{ $fila['fecha']->format('d/m/Y') }}
                        <br><span style="font-size:7.5pt">{{ $fila['fecha']->translatedFormat('l') }}</span>
                    </td>
                    <td>{!! str_replace(' | ', '<br>', e($fila['jornadas'])) !!}</td>
                    <td>{{ $fila['entradas'] }}</td>
                    <td>{{ $fila['salidas'] }}</td>
                    <td>{{ $fila['horas_completas'] }}</td>
                    <td>{{ $fila['tiempo_adicional'] }}</td>
                    <td class="{{ $fila['estado'] === 'No registro asistencia' ? 'sin-registro' : ($fila['estado'] === 'Sin salida registrada' ? 'sin-salida' : 'completo') }}">
                        {{ $fila['estado'] }}
                    </td>
                </tr>
            @endforeach
            <tr class="fila-totales">
                <td colspan="4">Total Horas | Apoyo al Área</td>
                <td>{{ $totales['cumplimiento_hm'] }}</td>
                <td>{{ $totales['apoyo_hm'] }}</td>
                <td></td>
            </tr>
        </tbody>
    </table>
@endsection