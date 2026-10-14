<?php

namespace App\Services;

use Illuminate\Support\Carbon;

/**
 * Rango de fechas de un documento de asistencia, ya normalizado.
 *
 * Concentra las reglas que comparten la bitacora administrativa y la del
 * pasante: nunca se pide mas alla de hoy, el inicio nunca supera al fin y el
 * rango se limita a un an para que dompdf siga siendo manejable.
 */
final class RangoFechas
{
    public readonly Carbon $desde;

    public readonly Carbon $hasta;

    public function __construct(Carbon $desde, Carbon $hasta)
    {
        $hoy = Carbon::today();

        if ($hasta->greaterThan($hoy)) {
            $hasta = $hoy->copy();
        }

        if ($desde->greaterThan($hasta)) {
            $desde = $hasta->copy();
        }

        // Tope de un an: los documentos dia a dia pasan por dompdf y con
        // rangos muy largos el PDF se vuelve ingestionable.
        if ($desde->lessThan($hasta->copy()->subYear())) {
            $desde = $hasta->copy()->subYear();
        }

        $this->desde = $desde;
        $this->hasta = $hasta->copy();
    }

    /**
     * Arma el rango a partir de los campos del formulario.
     *
     * El fin por defecto es hoy. Cuando no viene el inicio se usa
     * `$desdePorDefecto` si existe (el pasante parte de su fecha de ingreso) o,
     * si no, los ultimos 30 dias (bitacora administrativa).
     */
    public static function deInput(?string $desde, ?string $hasta, ?Carbon $desdePorDefecto = null): self
    {
        $hoy = Carbon::today();

        $hasta = $hasta !== null && $hasta !== ''
            ? Carbon::parse($hasta)->startOfDay()
            : $hoy->copy();

        if ($hasta->greaterThan($hoy)) {
            $hasta = $hoy->copy();
        }

        $desde = $desde !== null && $desde !== ''
            ? Carbon::parse($desde)->startOfDay()
            : ($desdePorDefecto?->copy()->startOfDay() ?? $hasta->copy()->subDays(29));

        return new self($desde, $hasta);
    }
}
