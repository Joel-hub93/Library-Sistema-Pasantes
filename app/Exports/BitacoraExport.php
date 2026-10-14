<?php

namespace App\Exports;

use App\Models\Pasante;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Bitacora de asistencias en Excel.
 *
 * Se arma con las mismas filas que el PDF (se le pasan ya calculadas desde
 * el controlador) para que los dos documentos digan exactamente lo mismo.
 * Nunca se guarda: solo se genera para la descarga.
 *
 * El estilo sigue la plantilla de las bitacoras del sistema: barra de
 * titulo azul, linea del pasante, encabezados en negrita y la columna
 * "Estado" coloreada (verde completo, amarillo sin salida, rojo ausente).
 */
class BitacoraExport implements FromView, WithEvents, WithStyles
{
    private const AZUL = 'FF10253F';

    private const VERDE = 'FF92D050';

    private const AMARILLO = 'FFFFFF66';

    private const ROJO = 'FFD99694';

    /** Fila donde empiezan los datos (1 titulo, 2 pasante, 3 encabezados). */
    private const PRIMERA_FILA_DATOS = 4;

    /** Columna del estado (A fecha ... G estado). */
    private const ULTIMA_COLUMNA = 'G';

    /**
     * @param  array<int, array{fecha: Carbon, jornadas: string, entradas: string, salidas: string, horas_completas: string, tiempo_adicional: string, estado: string}>  $filas
     * @param  array<string, mixed>  $totales
     */
    public function __construct(
        private Pasante $pasante,
        private array $filtros,
        private array $filas,
        private array $totales,
    ) {
    }

    /**
     * La vista que Excel convierte en hoja de calculo.
     */
    public function view(): View
    {
        return view('reportes.excel.bitacora', [
            'pasante' => $this->pasante,
            'filtros' => $this->filtros,
            'filas' => $this->filas,
            'totales' => $this->totales,
        ]);
    }

    public function styles(Worksheet $sheet): array
    {
        $ultimaFilaDatos = self::PRIMERA_FILA_DATOS + max(0, count($this->filas) - 1);

        $estilos = [
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
            2 => [
                'font' => ['size' => 12, 'name' => 'Calibri'],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
            3 => [
                'font' => ['bold' => true, 'size' => 11, 'name' => 'Calibri'],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
        ];

        if (count($this->filas) > 0) {
            $estilos["A4:G{$ultimaFilaDatos}"] = [
                'font' => ['size' => 11, 'name' => 'Calibri'],
            ];
            // Jornada, horarios y horas van centrados; fecha y estado a la izquierda.
            $estilos["B4:F{$ultimaFilaDatos}"] = [
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ];
        }

        $filaTotales = self::PRIMERA_FILA_DATOS + count($this->filas);
        $estilos[$filaTotales] = [
            'font' => [
                'bold' => true,
                'size' => 11,
                'name' => 'Calibri',
                'color' => ['argb' => 'FFFFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => self::AZUL],
            ],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ];
        // La columna de Estado de la fila de totales siempre queda vacia:
        // se pinta del mismo azul para que la barra se vea completa.
        $estilos[self::ULTIMA_COLUMNA.$filaTotales] = [
            'font' => [
                'size' => 11,
                'name' => 'Calibri',
                'color' => ['argb' => 'FFFFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => self::AZUL],
            ],
        ];

        return $estilos;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $sheet->getRowDimension(1)->setRowHeight(23.4);
                $sheet->getRowDimension(2)->setRowHeight(15);

                foreach (range('A', self::ULTIMA_COLUMNA) as $columna) {
                    $sheet->getColumnDimension($columna)->setAutoSize(true);
                }

                $this->colorearEstado($sheet);
            },
        ];
    }

    /**
     * Pinta la columna "Estado" segun el resultado del dia.
     */
    private function colorearEstado(Worksheet $sheet): void
    {
        $colores = [
            'Registro completo' => self::VERDE,
            'Sin salida registrada' => self::AMARILLO,
            'No registro asistencia' => self::ROJO,
        ];

        $ultimaFila = self::PRIMERA_FILA_DATOS + count($this->filas) - 1;

        for ($fila = self::PRIMERA_FILA_DATOS; $fila <= $ultimaFila; $fila++) {
            $estado = (string) $sheet->getCell(self::ULTIMA_COLUMNA.$fila)->getValue();

            if (! isset($colores[$estado])) {
                continue;
            }

            $sheet->getStyle(self::ULTIMA_COLUMNA.$fila)->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setARGB($colores[$estado]);
        }
    }
}
