<?php

namespace App\Http\Controllers;

use App\Exports\BitacoraExport;
use App\Http\Requests\BitacoraAsistenciaRequest;
use App\Models\Pasante;
use App\Services\BitacoraAsistencia;
use App\Services\RangoFechas;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\Response;

/**
 * Bitacoras de asistencia (PDF/Excel).
 *
 * Dos actores comparten el mismo documento:
 * - El administrador elige pasante y rango (`asistenciaPdf` / `asistenciaExcel`).
 * - El pasante ve la suya desde su fecha de ingreso (`miBitacoraPdf` /
 *   `miBitacoraExcel`).
 */
class ReporteBitacoraController extends Controller
{
    public function __construct(private readonly BitacoraAsistencia $bitacora)
    {
    }

    /**
     * Bitacora de asistencias en PDF, con pasante y rango de fechas.
     *
     * El campo "desde" no puede ser anterior a la fecha de ingreso del
     * pasante: el rango de la bitacora no puede inventar registros antes
     * de que el pasante existiera en el sistema.
     */
    public function asistenciaPdf(BitacoraAsistenciaRequest $request): Response
    {
        [$pasante, $filtros] = $this->pasanteConRango($request);

        $pdf = Pdf::loadView('reportes.pdf.bitacora', [
            'pasante' => $pasante,
            'filtros' => $filtros,
            'filas' => $this->bitacora->dias($pasante, $filtros['desde'], $filtros['hasta']),
            'totales' => $this->bitacora->totales($pasante, $filtros['desde'], $filtros['hasta']),
        ])->setPaper('letter', 'landscape');

        $nombre = 'bitacora_'.$pasante->cedula.'_'.$filtros['desde']->format('Ymd')
            .'_'.$filtros['hasta']->format('Ymd').'.pdf';

        return $pdf->download($nombre);
    }

    /**
     * Bitacora de asistencias en Excel, con pasante y rango de fechas.
     */
    public function asistenciaExcel(BitacoraAsistenciaRequest $request): Response
    {
        [$pasante, $filtros] = $this->pasanteConRango($request);

        $nombre = 'bitacora_'.$pasante->cedula.'_'.$filtros['desde']->format('Ymd')
            .'_'.$filtros['hasta']->format('Ymd').'.xlsx';

        return Excel::download(
            new BitacoraExport($pasante, $filtros, $this->bitacora->dias($pasante, $filtros['desde'], $filtros['hasta']), $this->bitacora->totales($pasante, $filtros['desde'], $filtros['hasta'])),
            $nombre
        );
    }

    /**
     * Bitacora propia del pasante: siempre disponible, para que vea como
     * va sin esperar a la administracion.
     */
    public function miBitacoraPdf(Request $request): Response
    {
        [$pasante, $filtros] = $this->filtrosPropios($request);

        $pdf = Pdf::loadView('reportes.pdf.bitacora', [
            'pasante' => $pasante,
            'filtros' => $filtros,
            'filas' => $this->bitacora->dias($pasante, $filtros['desde'], $filtros['hasta']),
            'totales' => $this->bitacora->totales($pasante, $filtros['desde'], $filtros['hasta']),
        ])->setPaper('letter', 'landscape');

        $nombre = 'bitacora_'.$pasante->cedula.'_'.$filtros['desde']->format('Ymd')
            .'_'.$filtros['hasta']->format('Ymd').'.pdf';

        return $pdf->download($nombre);
    }

    /**
     * Bitacora propia del pasante en Excel.
     */
    public function miBitacoraExcel(Request $request): Response
    {
        [$pasante, $filtros] = $this->filtrosPropios($request);

        $nombre = 'bitacora_'.$pasante->cedula.'_'.$filtros['desde']->format('Ymd')
            .'_'.$filtros['hasta']->format('Ymd').'.xlsx';

        return Excel::download(
            new BitacoraExport($pasante, $filtros, $this->bitacora->dias($pasante, $filtros['desde'], $filtros['hasta']), $this->bitacora->totales($pasante, $filtros['desde'], $filtros['hasta'])),
            $nombre
        );
    }

    /* -----------------------------------------------------------------
     | Validaciones y apoyos
     | -----------------------------------------------------------------
     */

    /**
     * Pasante y filtros validados de la bitacora.
     *
     * Valida el rango y, ademas, que el "desde" no sea anterior a la fecha
     * de ingreso del pasante: no se pueden pedir registros de una epoca en
     * la que el pasante aun no estaba en el sistema.
     *
     * @return array{0: Pasante, 1: array{pasante_id: int, desde: Carbon, hasta: Carbon}}
     */
    private function pasanteConRango(BitacoraAsistenciaRequest $request): array
    {
        $datos = $request->validated();
        $rango = $request->rango();

        $pasante = Pasante::with('carrera')->findOrFail($datos['pasante_id']);

        $ingreso = $pasante->fecha_ingreso?->copy()->startOfDay();

        if ($ingreso && $rango->desde->startOfDay()->lessThan($ingreso)) {
            throw ValidationException::withMessages([
                'desde' => 'La fecha inicial no puede ser anterior a la fecha de ingreso del pasante ('.$ingreso->format('d/m/Y').').',
            ]);
        }

        return [$pasante, [
            'pasante_id' => (int) $datos['pasante_id'],
            'desde' => $rango->desde,
            'hasta' => $rango->hasta,
        ]];
    }

    /**
     * Rango de fechas pedido, con los defaults del pasante autenticado:
     * desde su fecha de ingreso hasta hoy.
     *
     * @return array{0: Pasante, 1: array{desde: Carbon, hasta: Carbon}}
     */
    private function filtrosPropios(Request $request): array
    {
        $pasante = $request->user('pasante');

        $rango = RangoFechas::deInput(
            $request->filled('desde') ? (string) $request->input('desde') : null,
            $request->filled('hasta') ? (string) $request->input('hasta') : null,
            $pasante->fecha_ingreso?->copy() ?? Carbon::today(),
        );

        return [$pasante, ['desde' => $rango->desde, 'hasta' => $rango->hasta]];
    }
}
