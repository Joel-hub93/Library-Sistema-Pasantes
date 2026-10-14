<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Listado generico de un modulo del sistema.
 *
 * La primera fila es el titulo ("Listado de Usuarios", por ejemplo) y se
 * fusiona a lo ancho de TODAS las columnas (colspan en la vista); la
 * segunda fila lleva los encabezados y el resto son datos. Es un
 * documento de descarga: nunca se guarda en el sistema.
 *
 * El estilo sigue la plantilla de los listados del sistema: barra de
 * titulo azul con letra blanca, encabezados en negrita y la columna
 * "Estado" coloreada segun el valor (activo/inactivo).
 */
class ListadoExport implements FromView, WithEvents, WithStyles
{
    /** Azul institucional de la barra de titulo. */
    private const AZUL = 'FF10253F';

    /** Colores de la columna de estado. */
    private const VERDE = 'FF92D050';

    private const ROJO = 'FFD99694';

    /**
     * @param  array<int, string>  $encabezados
     * @param  Collection<int, array<int, mixed>>  $filas
     */
    public function __construct(
        private string $titulo,
        private array $encabezados,
        private Collection $filas,
    ) {
    }

    /**
     * La vista que Excel convierte en hoja de calculo.
     */
    public function view(): View
    {
        return view('reportes.excel.listado', [
            'titulo' => $this->titulo,
            'encabezados' => $this->encabezados,
            'filas' => $this->filas,
        ]);
    }

    public function styles(Worksheet $sheet): array
    {
        $ultimaColumna = $this->columna(count($this->encabezados) ?: 1);

        $estilos = [
            // Barra de titulo.
            1 => [
                'font' => [
                    'bold' => true,
                    'size' => 18,
                    'name' => 'Calibri',
                    'color' => ['argb' => 'FFFFFFFF'],
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['argb' => self::AZUL],
                ],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
            // Encabezados de columnas.
            2 => [
                'font' => ['bold' => true, 'size' => 11, 'name' => 'Calibri'],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
        ];

        $ultimaFila = $sheet->getHighestRow();

        if ($ultimaFila >= 3) {
            $estilos["A3:{$ultimaColumna}{$ultimaFila}"] = [
                'font' => ['size' => 11, 'name' => 'Calibri'],
            ];
        }

        return $estilos;
    }

    /**
     * Despues de escribir la hoja: alto de la barra de titulo, ancho de
     * columnas y color de la columna "Estado".
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $ultimaColumna = $this->columna(count($this->encabezados) ?: 1);

                $sheet->getRowDimension(1)->setRowHeight(23.4);

                foreach (range('A', $ultimaColumna) as $columna) {
                    $sheet->getColumnDimension($columna)->setAutoSize(true);
                }

                $this->colorearEstado($sheet);
            },
        ];
    }

    /**
     * Pinta la columna "Estado" en verde (activo) o rojo (inactivo).
     */
    private function colorearEstado(Worksheet $sheet): void
    {
        $indice = $this->indiceDeColumna('Estado');

        if ($indice === null) {
            return;
        }

        $columna = $this->columna($indice);
        $ultimaFila = $sheet->getHighestRow();

        for ($fila = 3; $fila <= $ultimaFila; $fila++) {
            $valor = (string) $sheet->getCell($columna.$fila)->getValue();

            if ($valor === '') {
                continue;
            }

            $color = str_starts_with($valor, 'Activ') ? self::VERDE : self::ROJO;

            $sheet->getStyle($columna.$fila)->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setARGB($color);
        }
    }

    /**
     * Posicion (1 => A) del encabezado indicado, o null si no existe.
     */
    private function indiceDeColumna(string $encabezado): ?int
    {
        foreach ($this->encabezados as $i => $texto) {
            if (mb_strtolower(trim($texto)) === mb_strtolower($encabezado)) {
                return $i + 1;
            }
        }

        return null;
    }

    /**
     * Letra de la columna N (1 => A, 2 => B, 27 => AA).
     */
    private function columna(int $n): string
    {
        $letra = '';

        while ($n > 0) {
            $n--;
            $letra = chr(65 + $n % 26).$letra;
            $n = intdiv($n, 26);
        }

        return $letra;
    }
}
