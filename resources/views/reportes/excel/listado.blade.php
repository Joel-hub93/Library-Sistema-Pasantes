<table>
    <thead>
        <tr>
            <th colspan="{{ count($encabezados) ?: 1 }}">{{ $titulo }}</th>
        </tr>
        <tr>
            @foreach ($encabezados as $tituloColumna)
                <th>{{ $tituloColumna }}</th>
            @endforeach
        </tr>
    </thead>
    <tbody>
        @foreach ($filas as $fila)
            <tr>
                @foreach ($fila as $celda)
                    <td>{{ $celda }}</td>
                @endforeach
            </tr>
        @endforeach
    </tbody>
</table>