<?php

namespace App\Http\Requests;

use App\Services\RangoFechas;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;

/**
 * Filtros de la bitacora administrativa de asistencias.
 *
 * El pasante es obligatorio; el rango de fechas es opcional y, ademas de
 * validarse, se normaliza con RangoFechas (tope a hoy, orden y un an maximo).
 */
class BitacoraAsistenciaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'pasante_id' => ['required', 'integer', 'exists:pasantes,id'],
            'desde' => ['nullable', 'date_format:Y-m-d', $this->reglaRangoOrdenado()],
            'hasta' => ['nullable', 'date_format:Y-m-d'],
        ];
    }

    public function messages(): array
    {
        return [
            'pasante_id.required' => 'Debe seleccionar un pasante.',
        ];
    }

    /**
     * Rango de fechas ya normalizado para armar la bitacora.
     */
    public function rango(): RangoFechas
    {
        $datos = $this->validated();

        return RangoFechas::deInput($datos['desde'] ?? null, $datos['hasta'] ?? null);
    }

    /**
     * El inicio no puede ser posterior al fin.
     */
    private function reglaRangoOrdenado(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) {
            $hasta = $this->input('hasta');

            if ($hasta !== null && $hasta !== '' && Carbon::parse($value)->greaterThan(Carbon::parse($hasta))) {
                $fail('La fecha inicial debe ser anterior o igual a la final.');
            }
        };
    }
}
