<?php

namespace App\Http\Controllers;

use App\Enums\Jornada;
use App\Http\Requests\MarcarAsistenciaRequest;
use App\Models\Asistencia;
use App\Services\FotoPrivada;
use App\Services\RegistroAsistencia;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Portal del pasante autenticado (guard 'pasante').
 *
 * Reune las pantallas de autoservicio: ver su estado de hoy, marcar entrada o
 * salida, consultar su historial y ver sus propias fotos. Las reglas del
 * autoregistro viven en RegistroAsistencia; este controlador solo traduce el
 * resultado a HTTP.
 */
class AsistenciaPortalController extends Controller
{
    public function __construct(
        private readonly FotoPrivada $fotos,
        private readonly RegistroAsistencia $registro,
    ) {
    }

    /**
     * Pantalla de registro de asistencia del pasante autenticado.
     */
    public function registrar(Request $request): View
    {
        $pasante = $request->user('pasante');
        $pasante->load('carrera');

        $turnosHoy = $pasante->asistencias()->enRango(Carbon::today(), Carbon::today())->get();

        return view('asistencias.registrar', [
            'pasante' => $pasante,
            'jornadas' => $this->jornadas(),
            'estadoJornadas' => $this->estadoJornadas($turnosHoy),
            'jornadaSeleccionada' => $this->jornadaSeleccionada($turnosHoy),
            'diaHabil' => Asistencia::esDiaHabil(Carbon::today()),
            'periodoCerrado' => $pasante->informeGenerado(),
        ]);
    }

    /**
     * Estado de entrada y salida de hoy por jornada, para la pantalla de marcado.
     *
     * @param  Collection<int, Asistencia>  $turnosHoy
     * @return array<string, array{entrada: bool, salida: bool, completo: bool}>
     */
    private function estadoJornadas($turnosHoy): array
    {
        $estado = [];

        foreach ($this->jornadas() as $jornada => $texto) {
            $turno = $turnosHoy->firstWhere('jornada', $jornada);

            $estado[$jornada] = [
                'entrada' => $turno !== null,
                'salida' => $turno?->completo() ?? false,
                'completo' => $turno?->completo() ?? false,
            ];
        }

        return $estado;
    }

    /**
     * La jornada con la que la pantalla arranca: la que aun tiene la salida
     * pendiente hoy, o la primera de la lista si no hay ningun turno.
     *
     * @param  Collection<int, Asistencia>  $turnosHoy
     */
    private function jornadaSeleccionada($turnosHoy): string
    {
        $pendiente = $turnosHoy->first(fn (Asistencia $turno) => ! $turno->completo());

        return $pendiente?->jornada ?? array_key_first($this->jornadas());
    }

    /**
     * Pantalla con el registro unitario del dia de hoy.
     *
     * El historial completo vive en historial(); aca el pasante solo ve como
     * quedo su turno de hoy y si le falta registrar la salida.
     */
    public function registros(Request $request): View
    {
        $pasante = $request->user('pasante');

        return view('asistencias.registros', [
            'pasante' => $pasante,
            'jornadas' => $this->jornadas(),
            'turnosHoy' => $pasante->asistencias()->enRango(Carbon::today(), Carbon::today())
                ->orderBy('jornada')
                ->get(),
        ]);
    }

    /**
     * Todos los registros del pasante, desde su fecha de ingreso hasta hoy.
     */
    public function historial(Request $request): View
    {
        $pasante = $request->user('pasante');

        // Se carga la relacion completa porque el avance de la carrera se
        // calcula sobre todos los turnos, no solo sobre los visibles.
        $pasante->load('asistencias');

        $desde = $pasante->fecha_ingreso ?? Carbon::today();
        $hasta = Carbon::today();

        // Se carga la relacion completa: el apoyo del dia y el estado del turno
        // se calculan en memoria, y el historial se filtra y ordena aca.
        $asistencias = $pasante->asistencias
            ->filter(fn (Asistencia $turno) => $turno->fecha->gte($desde) && $turno->fecha->lte($hasta))
            ->sort(function (Asistencia $a, Asistencia $b) {
                $porFecha = $b->fecha <=> $a->fecha;

                return $porFecha !== 0
                    ? $porFecha
                    : strcmp((string) $a->hora_entrada, (string) $b->hora_entrada);
            })
            ->values();

        // El apoyo al area es un dato del dia, no del turno: se suman los
        // minutos de todos los turnos del dia y se resta la meta diaria. Los
        // dias con mas de un turno se marcan para distinguirlos.
        $meta = Asistencia::minutosMetaDiarios();
        $apoyoPorDia = [];
        $diasCompartidos = [];

        $asistencias
            ->groupBy(fn (Asistencia $turno) => $turno->fecha->toDateString())
            ->each(function ($fila, $fecha) use (&$apoyoPorDia, &$diasCompartidos, $meta) {
                $registrados = $fila->sum(fn (Asistencia $turno) => $turno->minutosRegistrados());

                $apoyoPorDia[$fecha] = max(0, $registrados - $meta);

                if ($fila->count() > 1) {
                    $diasCompartidos[] = $fecha;
                }
            });

        return view('asistencias.historial', [
            'pasante' => $pasante,
            'jornadas' => $this->jornadas(),
            'asistencias' => $asistencias,
            'diasSinRegistro' => $this->registro->diasHabilesSinRegistro($pasante),
            'apoyoPorDia' => $apoyoPorDia,
            'diasCompartidos' => $diasCompartidos,
        ]);
    }

    /**
     * El pasante marca su entrada o su salida desde la misma pantalla.
     *
     * Es una sola accion y no dos porque el flujo es una vista unica: el
     * pasante elige que esta marcando, se toma la foto y se envia. La fecha y
     * la hora las toma el servidor, asi que el cliente no puede mandarlas. A
     * diferencia del administrador, el pasante no registra en sabado ni domingo.
     */
    public function marcar(MarcarAsistenciaRequest $request): RedirectResponse
    {
        $pasante = $request->user('pasante');

        if (! Asistencia::esDiaHabil(Carbon::today())) {
            return back()->with('error', 'No se registran asistencias en sábado ni domingo.');
        }

        if ($this->registro->periodoCerrado($pasante->id)) {
            return back()->with('error', 'Tu informe final ya fue generado: el periodo de registro quedo cerrado.');
        }

        $resultado = $request->validated('tipo') === 'entrada'
            ? $this->registro->marcarEntrada($pasante, $request->validated('jornada'), $request->file('foto'))
            : $this->registro->marcarSalida($pasante, $request->validated('jornada'), $request->file('foto'));

        return $resultado->exitoso
            ? redirect()->route('asistencias.registros')->with('confirmacion', $resultado->mensaje)
            : back()->with('error', $resultado->mensaje);
    }

    /**
     * Muestra al pasante las fotos de sus propios registros.
     *
     * Comparte el disco privado con la accion foto() del administrador, pero en
     * vez del permiso comprueba que el turno pertenezca al pasante que esta
     * autenticado: ningun pasante ve las fotos de otro.
     */
    public function fotoPublica(Request $request, Asistencia $asistencia, string $tipo): StreamedResponse
    {
        abort_if($asistencia->pasante_id !== $request->user('pasante')?->id, 404);

        return $this->fotos->servir($asistencia->rutaFoto($tipo));
    }

    /**
     * Jornadas disponibles.
     */
    private function jornadas(): array
    {
        return Jornada::opciones();
    }
}
