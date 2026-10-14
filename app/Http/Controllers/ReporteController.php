<?php

namespace App\Http\Controllers;

use App\Models\Pasante;
use App\Models\Reporte;
use App\Models\User;
use App\Services\BitacoraAsistencia;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Modulo de Reportes: vista raiz e informe final.
 *
 * - `index` es la tabla de modulos con sus botones de descarga.
 * - El informe final es el UNICO documento que se persiste: `vistaPrevia()`
 *   solo muestra como quedaria; `generar()` lo guarda en el disco privado y su
 *   fila en `reportes` cierra el periodo de registro del pasante.
 *
 * Los listados simples viven en ReporteListadoController y las bitacoras en
 * ReporteBitacoraController.
 */
class ReporteController extends Controller
{
    public function __construct(private readonly BitacoraAsistencia $bitacora)
    {
    }

    /**
     * Tabla de modulos del sistema con sus botones de descarga.
     *
     * Ademas se pasan los pasantes como JSON para los modales de
     * asistencias: el front los necesita para validar fechas (desde no
     * anterior a la fecha de ingreso) y para mostrar el estado del
     * informe final (pendiente / generado) por cada pasante.
     */
    public function index(): View
    {
        $pasantes = Pasante::with(['carrera', 'asistencias', 'reportes'])
            ->orderBy('apellidos')
            ->orderBy('nombres')
            ->get()
            ->map(function (Pasante $pasante) {
                $reporte = $pasante->reportes
                    ->firstWhere('estado', Reporte::ESTADO_ACTIVO);

                return [
                    'id' => $pasante->id,
                    'nombre' => trim($pasante->apellidos.' '.$pasante->nombres),
                    'cedula' => $pasante->cedula,
                    'fecha_ingreso' => $pasante->fecha_ingreso?->format('Y-m-d'),
                    'completo' => $pasante->completoPracticas(),
                    'informe' => $reporte !== null,
                    'reporte_id' => $reporte?->id,
                    'url_descargar_informe' => $reporte ? route('reportes.informe', $reporte) : null,
                    'url_anular' => $reporte ? route('reportes.anular', $reporte) : null,
                ];
            });

        return view('reportes.index', ['pasantes' => $pasantes]);
    }

    /* -----------------------------------------------------------------
     | Informe final
     | -----------------------------------------------------------------
     */

    /**
     * Vista previa del informe: descarga el PDF con los datos actuales
     * hasta hoy con la misma plantilla que el informe definitivo, pero NO
     * se guarda ni cierra el periodo de registro.
     */
    public function vistaPrevia(Request $request): Response
    {
        $pasante = $this->pasanteDelRequest($request);

        return $this->informePdf($pasante, $request->user(), now())
            ->download('vista_previa_informe_'.$pasante->cedula.'.pdf');
    }

    /**
     * Genera el informe final: guarda el PDF en el disco privado y crea la
     * fila activa que cierra el periodo de registro del pasante.
     *
     * Solo se puede generar una vez por pasante y solo cuando ya cumple
     * las horas de practica definidas por su carrera.
     */
    public function generar(Request $request): RedirectResponse
    {
        $pasante = $this->pasanteDelRequest($request);

        if ($pasante->informeGenerado()) {
            return back()->with('error', 'Ese pasante ya tiene su informe final generado.');
        }

        if (! $pasante->completoPracticas()) {
            return back()->with('error', 'El pasante aun no cumple las horas de practica de su carrera.');
        }

        $ruta = 'reportes/informe_'.$pasante->id.'_'.now()->format('Ymd_His').'.pdf';

        $this->informePdf($pasante, $request->user(), now())
            ->setPaper('letter')
            ->save($ruta, 'local');

        Reporte::create([
            'pasante_id' => $pasante->id,
            'ruta_pdf' => $ruta,
            'generado_por' => $request->user()->id,
            'fecha_generacion' => now(),
            'estado' => Reporte::ESTADO_ACTIVO,
        ]);

        return redirect()->route('reportes.index')
            ->with('confirmacion', 'Informe final generado. El periodo de registro del pasante quedo cerrado.');
    }

    /**
     * Descarga el PDF del informe guardado.
     */
    public function descargarInforme(Reporte $reporte): Response
    {
        abort_unless($reporte->activo(), 404);
        abort_unless(Storage::disk('local')->exists($reporte->ruta_pdf), 404);

        $nombre = 'informe_'.$reporte->pasante->cedula.'_'
            .$reporte->fecha_generacion->format('Ymd').'.pdf';

        return Storage::disk('local')->download($reporte->ruta_pdf, $nombre);
    }

    /**
     * Anula el informe: borra el PDF, marca la fila como anulada y con eso
     * reabre el registro de asistencias del pasante (y le quita la
     * descarga del informe hasta que se vuelva a generar).
     */
    public function anular(Reporte $reporte): RedirectResponse
    {
        if (! $reporte->activo()) {
            return back()->with('error', 'Ese informe ya estaba anulado.');
        }

        Storage::disk('local')->delete($reporte->ruta_pdf);
        $reporte->update(['estado' => Reporte::ESTADO_ANULADO]);

        return back()->with('confirmacion', 'Informe anulado: el periodo de registro del pasante se reabrio.');
    }

    /**
     * Informe final propio del pasante: solo si la administracion ya lo genero.
     * Si no, 404: el pasante nunca ve un documento que aun no existe.
     */
    public function miInforme(Request $request): Response
    {
        $pasante = $request->user('pasante');

        $reporte = $pasante->reporteActivo();

        abort_if(! $reporte || ! Storage::disk('local')->exists($reporte->ruta_pdf), 404);

        $nombre = 'informe_'.$pasante->cedula.'_'
            .$reporte->fecha_generacion->format('Ymd').'.pdf';

        return Storage::disk('local')->download($reporte->ruta_pdf, $nombre);
    }

    /* -----------------------------------------------------------------
     | Armado del informe final
     | -----------------------------------------------------------------
     */

    /**
     * PDF del informe con la plantilla compartida entre vista previa y
     * generacion: los dos documentos deben verse identicos.
     *
     * El detalle dia a dia NO va en el informe: quedo en las bitacoras. Aqui
     * solo viajan los totales del periodo, los datos del pasante y el
     * promedio de asistencia para armar la solicitud formal.
     */
    private function informePdf(Pasante $pasante, User $generadoPor, Carbon $fechaGeneracion)
    {
        return Pdf::loadView('reportes.pdf.informe', [
            'pasante' => $pasante,
            'totales' => $this->bitacora->totales($pasante),
            'promedioAsistencia' => $pasante->promedioAsistencia(),
            'generadoPor' => $generadoPor,
            'fechaGeneracion' => $fechaGeneracion,
        ])->setPaper('letter');
    }

    /**
     * Pasante pedido por el formulario.
     */
    private function pasanteDelRequest(Request $request): Pasante
    {
        $datos = $request->validate([
            'pasante_id' => ['required', 'integer', 'exists:pasantes,id'],
        ]);

        return Pasante::with('carrera')->findOrFail($datos['pasante_id']);
    }
}
