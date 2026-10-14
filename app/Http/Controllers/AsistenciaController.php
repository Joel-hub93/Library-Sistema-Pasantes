<?php

namespace App\Http\Controllers;

use App\Enums\Jornada;
use App\Http\Requests\FiltroAsistenciaRequest;
use App\Http\Requests\StoreAsistenciaRequest;
use App\Http\Requests\UpdateAsistenciaRequest;
use App\Models\Asistencia;
use App\Models\Pasante;
use App\Services\FotoPrivada;
use App\Services\RegistroAsistencia;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Gestion de asistencias del administrador (guard 'web').
 *
 * Cubre el CRUD manual: registrar un turno, corregirlo, eliminarlo y servir
 * las fotos con permiso. El autoregistro del pasante vive en
 * AsistenciaPortalController, para que cada controlador responda a un solo
 * actor.
 */
class AsistenciaController extends Controller
{
    public function __construct(
        private readonly FotoPrivada $fotos,
        private readonly RegistroAsistencia $registro,
    ) {
    }

    /* -----------------------------------------------------------------
     | Gestion de asistencias (administradores)
     | -----------------------------------------------------------------
     */

    /**
     * Lista las asistencias con sus filtros opcionales de pasante y fecha.
     */
    public function index(FiltroAsistenciaRequest $request): View
    {
        $filtros = $request->validated();

        $asistencias = Asistencia::with('pasante.carrera')
            ->when($filtros['pasante_id'] ?? null, fn ($q, $id) => $q->where('pasante_id', $id))
            ->when($filtros['fecha'] ?? null, fn ($q, $fecha) => $q->whereDate('fecha', $fecha))
            ->orderByDesc('fecha')
            ->orderBy('id')
            ->get();

        // Igual que en el historial, el apoyo es un dato del dia: se suman los
        // minutos de todos los turnos del dia y se resta la meta diaria. Los
        // dias con mas de un turno se marcan para distinguirlos en la tabla y
        // que el administrador vea que hubo doble registro.
        $meta = Asistencia::minutosMetaDiarios();
        $apoyoPorDia = [];
        $diasCompartidos = [];

        $asistencias
            ->groupBy(fn (Asistencia $turno) => $turno->pasante_id.'|'.$turno->fecha->toDateString())
            ->each(function ($fila, $clave) use (&$apoyoPorDia, &$diasCompartidos, $meta) {
                $registrados = $fila->sum(fn (Asistencia $turno) => $turno->minutosRegistrados());

                $apoyoPorDia[$clave] = max(0, $registrados - $meta);

                if ($fila->count() > 1) {
                    $diasCompartidos[] = $clave;
                }
            });

        return view('asistencias.index', array_merge($this->catalogos(), [
            'asistencias' => $asistencias,
            'filtros' => $filtros,
            'apoyoPorDia' => $apoyoPorDia,
            'diasCompartidos' => $diasCompartidos,
        ]));
    }

    /**
     * Muestra el formulario para registrar una asistencia a mano.
     */
    public function create(): View
    {
        return view('asistencias.create', $this->catalogos());
    }

    /**
     * Guarda la nueva asistencia en la base de datos.
     *
     * Las fotos son opcionales en la gestion administrativa, a diferencia del
     * flujo del pasante. El total de horas no se guarda: se calcula siempre
     * restando la salida a la entrada.
     */
    public function store(StoreAsistenciaRequest $request): RedirectResponse
    {
        $datos = $request->safe()->except(['foto_entrada', 'foto_salida']);

        if ($this->registro->periodoCerrado((int) $datos['pasante_id'])) {
            return redirect()->route('asistencias.create')
                ->with('error', 'Ese pasante ya tiene su informe final: el periodo de registro esta cerrado.');
        }

        $asistencia = Asistencia::create($datos);

        if ($request->hasFile('foto_entrada')) {
            $asistencia->foto_entrada = $this->fotos->guardar(
                $request->file('foto_entrada'),
                Asistencia::CARPETA_FOTOS.'/entrada'
            );
        }

        if ($request->hasFile('foto_salida')) {
            $asistencia->foto_salida = $this->fotos->guardar(
                $request->file('foto_salida'),
                Asistencia::CARPETA_FOTOS.'/salida'
            );
        }

        $asistencia->save();

        return redirect()->route('asistencias.show', $asistencia->id)
            ->with('confirmacion', 'Asistencia registrada correctamente');
    }

    /**
     * Muestra el detalle completo de una asistencia.
     */
    public function show(Asistencia $asistencia): View
    {
        $asistencia->load('pasante.carrera');

        return view('asistencias.show', [
            'asistencia' => $asistencia,
            'jornadas' => $this->jornadas(),
        ]);
    }

    /**
     * Muestra el formulario para corregir una asistencia existente.
     *
     * Solo se editan fecha, jornada y horas: las fotos del registro original se
     * conservan y no se pueden reemplazar desde aqui.
     */
    public function edit(Asistencia $asistencia): View
    {
        $asistencia->load('pasante.carrera');

        return view('asistencias.edit', array_merge($this->catalogos(), compact('asistencia')));
    }

    /**
     * Actualiza fecha, jornada y horas de una asistencia.
     *
     * No se tocan las fotos: si el turno ya tenia, se conservan intactas.
     */
    public function update(UpdateAsistenciaRequest $request, Asistencia $asistencia): RedirectResponse
    {
        if ($this->registro->periodoCerrado($asistencia->pasante_id)) {
            return redirect()->route('asistencias.show', $asistencia->id)
                ->with('error', 'Ese pasante ya tiene su informe final: el periodo de registro esta cerrado.');
        }

        $asistencia->update($request->validated());

        return redirect()->route('asistencias.show', $asistencia->id)
            ->with('confirmacion', 'Asistencia actualizada correctamente');
    }

    /**
     * Elimina una asistencia y sus fotos del disco.
     */
    public function destroy(Asistencia $asistencia): RedirectResponse
    {
        $this->fotos->borrar($asistencia->foto_entrada);
        $this->fotos->borrar($asistencia->foto_salida);

        $asistencia->delete();

        return redirect()->route('asistencias.index')
            ->with('confirmacion', 'Asistencia eliminada correctamente');
    }

    /**
     * Muestra una foto de asistencia a quien tenga permiso de verlas.
     *
     * Las fotos no se sirven como archivo publico: se leen del disco privado y
     * se devuelven solo si la asistencia existe y el usuario esta autenticado.
     */
    public function foto(Asistencia $asistencia, string $tipo): StreamedResponse
    {
        return $this->fotos->servir($asistencia->rutaFoto($tipo));
    }

    /* -----------------------------------------------------------------
     | Consultas de apoyo para los formularios
     | -----------------------------------------------------------------
     */

    /**
     * Pasantes activos y jornadas para los formularios de asistencia.
     */
    private function catalogos(): array
    {
        return [
            'pasantes' => Pasante::with(['carrera', 'reportes' => fn ($q) => $q->activos()])
                ->where('estado', '1')
                ->orderBy('apellidos')
                ->orderBy('nombres')
                ->get(),
            'jornadas' => $this->jornadas(),
        ];
    }

    /**
     * Jornadas disponibles para los formularios.
     */
    private function jornadas(): array
    {
        return Jornada::opciones();
    }
}
