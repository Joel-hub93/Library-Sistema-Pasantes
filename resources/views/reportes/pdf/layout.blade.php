@php
    // Membrete: imagenes embebidas en base64 para que dompdf las encuentre
    // sin depender de DOMPDF_ENABLE_REMOTE.
    $imagenEncabezado = 'data:image/png;base64,'.base64_encode(file_get_contents(public_path('img/documentos/encabezado.png')));
    $imagenPie = 'data:image/png;base64,'.base64_encode(file_get_contents(public_path('img/documentos/pie-pagina.png')));

    // Linea de fecha: la ciudad es fija y solo la fecha es dinamica.
    $fechaLarga = ucfirst(now()->locale('es')->translatedFormat('l j \de F \de Y'));
    $lineaFecha = 'Quito, '.$fechaLarga;
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>@yield('titulo')</title>

    <style>
        /* ============================================================
         * Ajustes del documento PDF.
         * Este es el unico lugar donde se mueven los tamanos y margenes.
         * "apaisado" = horizontal (listados y bitacora); "vertical" = informe.
         *
         * Cada documento puede sobrescribir SOLO lo que necesite, sin afectar
         * a los demas, definiendo estas secciones en su propia vista:
         *   seccion "orientacion"      -> landscape | portrait
         *   seccion "encabezado"       -> achatado | natural
         *   seccion "margen_superior"  -> 'Xcm' (mueve fecha/titulo/tabla)
         *   seccion "estilos"          -> reglas propias (ej. centrar tabla)
         * ============================================================ */

        @page {
            size: letter @yield('orientacion', 'landscape');
            margin: @yield('margen_superior', '4.9cm') 1.5cm 2.9cm 1.5cm;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 9pt;
            color: #000000;
            margin: 0;
        }

        h1 {
            font-size: 14pt;
            text-align: center;
            color: #000000;
            margin: 0 0 8pt 0;
            text-transform: uppercase;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            border: 1px solid #555;
            padding: 4pt 5pt;
            font-size: 9pt;
            color: #000000;
        }

        th {
            background-color: #dce6f1;
            font-weight: bold;
        }

        /* dompdf mide position: fixed respecto al area de contenido, por eso
           el encabezado y el pie se compensan con los margenes negativos de
           @page para quedar pegados a los bordes de la hoja. */
        .encabezado-documento,
        .pie-documento {
            position: fixed;
            left: -1.5cm;
            right: -1.5cm;
        }

        .encabezado-documento {
            top: -@yield('margen_superior', '4.9cm');
            text-align: center;
        }

        .encabezado-documento img {
            display: block;
            margin: 0 auto;
        }

        /* Apaisado: estirado a todo el ancho con altura fija (achatado). */
        .encabezado-documento.achatado img {
            width: 11in;
            height: 1.7in;
        }

        /* Vertical: proporcion natural de la imagen. */
        .encabezado-documento.natural img {
            width: 8.5in;
            height: auto;
        }

        .pie-documento {
            bottom: -2.0cm;
            text-align: center;
        }

        .pie-documento img {
            width: 15.5cm;
            height: auto;
        }

        .fecha-documento {
            text-align: right;
            font-size: 9pt;
            color: #000000;
            margin-bottom: 4pt;
        }

        @yield('estilos')
    </style>
</head>
<body>

    <div class="encabezado-documento @yield('encabezado', 'achatado')">
        <img src="{{ $imagenEncabezado }}" alt="">
    </div>

    <div class="pie-documento">
        <img src="{{ $imagenPie }}" alt="">
    </div>

    <div class="fecha-documento">{{ $lineaFecha }}</div>

    @yield('contenido')

</body>
</html>
