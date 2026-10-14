@extends('reportes.pdf.layout')

@section('titulo', 'Solicitud de culminación de prácticas — '.$pasante->cedula)
@section('orientacion', 'portrait')
@section('margen_superior', '5.0cm')
@section('encabezado', 'natural')

@section('estilos')
    @page {
        margin: 5.0cm 1.5cm 2.5cm 1.5cm;
    }

    body {
        font-size: 10.5pt;
    }

    h1 {
        margin-bottom: 12pt;
    }

    .destinatario {
        margin: 0 0 12pt 0;
        font-size: 10.5pt;
        line-height: 1.45;
    }

    .parrafo {
        text-align: justify;
        line-height: 1.45;
        margin: 0 0 12pt 0;
    }

    table {
        margin-bottom: 12pt;
    }

    .tabla-datos th,
    .tabla-datos td,
    .tabla-aspectos th,
    .tabla-aspectos td {
        padding: 4pt 8pt;
    }

    .tabla-datos th {
        width: 34%;
        text-align: left;
        background-color: #f2f2f2;
    }

    .tabla-datos td {
        text-align: left;
    }

    .tabla-aspectos th {
        text-align: center;
    }

    .tabla-aspectos td {
        text-align: center;
    }

    .tabla-aspectos .etiqueta {
        text-align: left;
        font-weight: bold;
    }

    .estado-completo {
        background-color: #1e7d32;
        color: #ffffff;
        font-weight: bold;
    }

    .estado-incompleto {
        background-color: #c00000;
        color: #ffffff;
        font-weight: bold;
    }

    /* La solicitud y las firmas van a una segunda hoja. */
    .segunda-hoja {
        page-break-before: always;
    }

    .firmas {
        margin-top: 60pt;
        page-break-inside: avoid;
    }

    .firmas table {
        border: none;
        margin: 0;
    }

    .firmas td {
        border: none;
        text-align: center;
        width: 50%;
        vertical-align: bottom;
        padding: 50pt 10pt 6pt 10pt;
    }

    /* La segunda fila de firmas lleva mas aire respecto de la primera. */
    .firmas .firmas-segunda-fila td {
        padding-top: 100pt;
    }

    .firma-linea {
        border-top: 1px solid #333;
        padding-top: 4pt;
        font-size: 9.5pt;
        font-weight: bold;
    }

    .firma-cargo {
        font-size: 8.5pt;
        color: #000000;
        font-weight: normal;
    }
@endsection

@section('contenido')
    @php
        // Horas de practica cumplidas: numero sin ceros de relleno (480, 47.5).
        $horas = $pasante->horasCumplidas();
        $horasTexto = rtrim(rtrim(number_format($horas, 2, '.', ''), '0'), '.');
        if ($horasTexto === '') {
            $horasTexto = '0';
        }

        $completo = $pasante->completoPracticas();

        // Firmas con valores por defecto (no se toman de la base de datos):
        // el responsable de Biblioteca/TI y la Secretaria de Campus Norte.
        // Solo el pasante y el tutor salen de los datos.
        $firmas = [
            ['Aldás Garcia Joel Edilberto', 'Bibliotecario - Responsable TI CN'],
            ['Jacome Olmedo Estefanía Gisela', 'Secretaria Campus Norte'],
            [$pasante->apellidos.' '.$pasante->nombres, 'Pasante'],
            [
                $pasante->tutor ? $pasante->tutor->apellidos.' '.$pasante->tutor->nombres : '',
                'Docente Tutor',
            ],
        ];
    @endphp

    <h1>Solicitud de Culminación de Prácticas</h1>

    <div class="destinatario">
        Ing. Estefanía Jacome<br>
        <strong>Secretaria de Campus Norte</strong><br>
        <strong>Instituto Tecnológico Superior Vida Nueva</strong>
    </div>

    <p class="parrafo">De mi consideración:</p>

    <p class="parrafo">
        Por medio de la presente, certifico que el ciudadano
        <strong>{{ $pasante->apellidos }} {{ $pasante->nombres }}</strong>, portador de la cédula
        de identidad N° <strong>{{ $pasante->cedula }}</strong>, estudiante de la carrera de
        <strong>{{ $pasante->carrera?->nombre ?? '—' }}</strong>, realizó sus prácticas
        profesionales en esta dependencia a partir del
        <strong>{{ $pasante->fecha_ingreso?->format('d/m/Y') ?? '—' }}</strong>, culminándolas de
        manera satisfactoria con un total de <strong>{{ $horasTexto }} horas cumplidas</strong>,
        distribuidas de acuerdo al siguiente cuadro:
    </p>

    <table class="tabla-datos">
        <tr>
            <th>Cédula de identidad:</th>
            <td>{{ $pasante->cedula }}</td>
        </tr>
        <tr>
            <th>Apellidos y nombres:</th>
            <td>{{ $pasante->apellidos }} {{ $pasante->nombres }}</td>
        </tr>
        <tr>
            <th>Carrera:</th>
            <td>{{ $pasante->carrera?->nombre ?? '—' }}</td>
        </tr>
        <tr>
            <th>Tutor académico:</th>
            <td>
                @if ($pasante->tutor)
                    {{ $pasante->tutor->apellidos }} {{ $pasante->tutor->nombres }}
                @else
                    —
                @endif
            </td>
        </tr>
        <tr>
            <th>Fecha de inicio de prácticas:</th>
            <td>{{ $pasante->fecha_ingreso?->format('d/m/Y') ?? '—' }}</td>
        </tr>
        <tr>
            <th>Área:</th>
            <td>Biblioteca | Tecnologías de la Información Campus Norte</td>
        </tr>
    </table>

    <table class="tabla-aspectos">
        <tr>
            <th style="width: 60%">Aspecto</th>
            <th style="width: 40%">Datos</th>
        </tr>
        <tr>
            <td class="etiqueta">Promedio de Asistencia</td>
            <td>{{ number_format($promedioAsistencia, 2) }} %</td>
        </tr>
        <tr>
            <td class="etiqueta">Horas de apoyo al área</td>
            <td>{{ number_format($totales['apoyo'], 1) }} h</td>
        </tr>
        <tr>
            <td class="etiqueta">Horas completadas de practicas</td>
            <td>{{ $horasTexto }} h</td>
        </tr>
        <tr>
            <td class="etiqueta">Estado de Practicas</td>
            <td class="{{ $completo ? 'estado-completo' : 'estado-incompleto' }}">
                {{ $completo ? 'Completo' : 'Incompleto' }}
            </td>
        </tr>
    </table>

    {{-- Segunda hoja: solicitud y las cuatro firmas. --}}
    <div class="segunda-hoja">
        <p class="parrafo">
            En virtud de lo antes expuesto, se solicita al Departamento de Secretaría de Campus Norte
            se sirva gestionar la emisión del certificado de finalización de prácticas profesionales
            correspondiente.
        </p>

        <p class="parrafo">Para constancia,</p>

        <div class="firmas">
            <table>
                @foreach (array_chunk($firmas, 2) as $indice => $fila)
                    <tr class="{{ $indice > 0 ? 'firmas-segunda-fila' : '' }}">
                        @foreach ($fila as [$nombre, $cargo])
                            <td>
                                <div class="firma-linea">
                                    {{ $nombre !== '' ? $nombre : '_______________________' }}
                                    <div class="firma-cargo">{{ $cargo }}</div>
                                </div>
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </table>
        </div>
    </div>
@endsection
