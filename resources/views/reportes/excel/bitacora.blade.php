<table>
    <thead>
        <tr>
            <th colspan="7">BITÁCORA DE REGISTROS DE ASISTENCIA</th>
        </tr>
        <tr>
            <th colspan="7">
                {{ $pasante->apellidos }} {{ $pasante->nombres }} — {{ $pasante->cedula }}
                — {{ $pasante->carrera?->nombre ?? 'Sin carrera' }}
            </th>
        </tr>
        <tr>
            <th>Fecha</th>
            <th>Jornada/s</th>
            <th>Ingreso/s</th>
            <th>Salida/s</th>
            <th>Horas Completas</th>
            <th>Tiempo Adicional</th>
            <th>Estado</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($filas as $fila)
            <tr>
                <td>{{ $fila['fecha']->format('d/m/Y') }}</td>
                <td>{{ $fila['jornadas'] }}</td>
                <td>{{ $fila['entradas'] }}</td>
                <td>{{ $fila['salidas'] }}</td>
                <td>{{ $fila['horas_completas'] }}</td>
                <td>{{ $fila['tiempo_adicional'] }}</td>
                <td>{{ $fila['estado'] }}</td>
            </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr>
            <th colspan="4">Total Horas | Apoyo al Área</th>
            <th>{{ $totales['cumplimiento_hm'] }}</th>
            <th>{{ $totales['apoyo_hm'] }}</th>
            <th></th>
        </tr>
    </tfoot>
</table>
