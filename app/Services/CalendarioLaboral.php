<?php

namespace App\Services;

use App\Models\Feriado;
use Illuminate\Support\Carbon;

/**
 * Decide si una fecha es habil.
 *
 * Un dia es habil si no es sabado, no es domingo y no esta en la tabla de
 * feriados. Guarda en memoria los feriados de cada anio para no consultar la
 * base de datos en cada vuelta de un bucle.
 */
final class CalendarioLaboral
{
    /** @var array<int, array<string, Feriado>> */
    private array $porAnio = [];

    public function esHabil(Carbon|string $fecha): bool
    {
        $dia = Carbon::parse($fecha)->startOfDay();

        return ! $dia->isWeekend() && ! $this->esFeriado($dia);
    }

    public function esFeriado(Carbon|string $fecha): bool
    {
        return $this->feriado($fecha) !== null;
    }

    public function feriado(Carbon|string $fecha): ?Feriado
    {
        $dia = Carbon::parse($fecha)->startOfDay();
        $anio = $dia->year;

        if (! isset($this->porAnio[$anio])) {
            $this->porAnio[$anio] = Feriado::query()
                ->whereYear('fecha', $anio)
                ->get()
                ->keyBy(fn (Feriado $feriado) => $feriado->fecha->toDateString())
                ->all();
        }

        return $this->porAnio[$anio][$dia->toDateString()] ?? null;
    }

    public function limpiar(): void
    {
        $this->porAnio = [];
    }
}
