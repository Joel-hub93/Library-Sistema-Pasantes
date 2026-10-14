<?php

namespace App\Services;

use App\Models\Asistencia;
use App\Models\Pasante;
use Illuminate\Support\Carbon;

/**
 * Armado de la bitacora de asistencias: la tabla dia a dia y los totales del
 * periodo.
 *
 * Vive en un servicio y no en el controlador porque es logica de dominio: sabe
 * de la meta diaria, de los dias habiles y de como se reparten las horas entre
 * practica (tope de la meta) y apoyo al area. La usan las cuatro descargas de
 * bitacora y el informe final.
 */
class BitacoraAsistencia
{
    /**
     * Filas de la bitacora: un renglon por dia habil del rango.
     *
     * Los dias habiles sin turno tambien se listan (con "No registro
     * asistencia") para que el hueco quede visible en el documento en vez de
     * desaparecer.
     *
     * @return array<int, array{fecha: Carbon, horario: string, total: string, estado: string, jornadas: string, entradas: string, salidas: string, horas_completas: string, tiempo_adicional: string}>
     */
    public function dias(Pasante $pasante, Carbon $desde, Carbon $hasta): array
    {
        $meta = Asistencia::minutosMetaDiarios();

        $turnos = $pasante->asistencias()
            ->enRango($desde, $hasta)
            ->orderBy('fecha')
            ->orderBy('id')
            ->get()
            ->groupBy(fn ($turno) => $turno->fecha->toDateString());

        $filas = [];

        for ($dia = $desde->copy(); $dia->lessThanOrEqualTo($hasta); $dia->addDay()) {
            if (! Asistencia::esDiaHabil($dia)) {
                continue;
            }

            $delDia = $turnos->get($dia->toDateString());

            if (! $delDia) {
                $filas[] = [
                    'fecha' => $dia->copy(),
                    'horario' => '—',
                    'total' => '00:00',
                    'estado' => 'No registro asistencia',
                    'jornadas' => '—',
                    'entradas' => '—',
                    'salidas' => '—',
                    'horas_completas' => '00:00',
                    'tiempo_adicional' => '00:00',
                ];

                continue;
            }

            $entradas = $delDia->map(fn ($t) => $t->hora_entrada)->implode(' / ');
            $salidas = $delDia->map(fn ($t) => $t->hora_salida ?? '—')->implode(' / ');
            $minutos = $delDia->sum(fn ($t) => $t->minutosRegistrados());

            $filas[] = [
                'fecha' => $dia->copy(),
                'horario' => $entradas.' a '.$salidas,
                'total' => Asistencia::formatoHoras($minutos),
                'estado' => $delDia->contains(fn ($t) => ! $t->completo())
                    ? 'Sin salida registrada'
                    : 'Registro completo',
                // Columnas del Excel de bitacora: jornadas y horarios por turno,
                // luego las horas del dia repartidas entre practica (tope de la
                // meta) y tiempo adicional a la meta.
                'jornadas' => $delDia->map(fn ($t) => ucfirst($t->jornada))->implode(' | '),
                'entradas' => $delDia->map(fn ($t) => $t->hora_entrada)->implode(' | '),
                'salidas' => $delDia->map(fn ($t) => $t->hora_salida ?? '—')->implode(' | '),
                'horas_completas' => Asistencia::formatoHoras(min($minutos, $meta)),
                'tiempo_adicional' => Asistencia::formatoHoras(max(0, $minutos - $meta)),
            ];
        }

        return $filas;
    }

    /**
     * Totales del periodo: horas de practica y horas de apoyo.
     *
     * Se calculan con las mismas filas que las tablas (solo dias habiles), asi
     * el pie del documento cuadra siempre con las columnas visibles: las
     * asistencias de fin de semana que el administrador pueda cargar no se
     * cuelan en los totales de un listado que no las muestra.
     *
     * @return array{cumplimiento: float, apoyo: float, registrado: float, cumplimiento_hm: string, apoyo_hm: string, registrado_hm: string}
     */
    public function totales(Pasante $pasante, ?Carbon $desde = null, ?Carbon $hasta = null): array
    {
        $meta = Asistencia::minutosMetaDiarios();

        $turnos = $desde === null
            ? $pasante->asistencias
            : $pasante->asistencias()->enRango($desde, $hasta)->get();

        $turnos = $turnos->filter(fn ($turno) => Asistencia::esDiaHabil($turno->fecha));

        $cumplimiento = 0;
        $apoyo = 0;

        foreach ($turnos->groupBy(fn ($turno) => $turno->fecha->toDateString()) as $delDia) {
            $registrados = $delDia->sum(fn ($turno) => $turno->minutosRegistrados());
            $cumplimiento += min($registrados, $meta);
            $apoyo += max(0, $registrados - $meta);
        }

        return [
            'cumplimiento' => round($cumplimiento / 60, 2),
            'apoyo' => round($apoyo / 60, 2),
            // Suma cruda de la columna "Total horas" de la bitacora: practica
            // mas apoyo del periodo.
            'registrado' => round(($cumplimiento + $apoyo) / 60, 2),
            // Formato H:i listo para las tablas de los documentos
            'cumplimiento_hm' => Asistencia::formatoHoras($cumplimiento),
            'apoyo_hm' => Asistencia::formatoHoras($apoyo),
            'registrado_hm' => Asistencia::formatoHoras($cumplimiento + $apoyo),
        ];
    }
}
