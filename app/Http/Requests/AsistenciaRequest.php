<?php

namespace App\Http\Requests;

use App\Enums\Jornada;
use App\Models\Asistencia;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Base de los formularios de asistencia del administrador.
 *
 * Reune la validacion comun a crear y editar: pasante, fecha (con la regla
 * anti-duplicados), jornada y horas. Los mensajes en espanol viven aca una
 * sola vez para que crear y editar no se desincronicen.
 */
abstract class AsistenciaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function messages(): array
    {
        return [
            'pasante_id.required' => 'Debe seleccionar un pasante.',
            'pasante_id.exists' => 'El pasante seleccionado no es valido.',
            'fecha.required' => 'Debe seleccionar una fecha.',
            'fecha.date_format' => 'La fecha no tiene el formato correcto.',
            'jornada.required' => 'Debe seleccionar una jornada.',
            'jornada.in' => 'La jornada seleccionada no es valida.',
            'hora_entrada.required' => 'Debe indicar la hora de entrada.',
            'hora_entrada.date_format' => 'La hora de entrada no tiene el formato correcto.',
            'hora_salida.date_format' => 'La hora de salida no tiene el formato correcto.',
            'hora_salida.after' => 'La hora de salida no puede ser anterior a la de entrada.',
            'foto_entrada.image' => 'La foto debe ser una imagen.',
            'foto_entrada.max' => 'La foto no debe pesar mas de 2MB.',
            'foto_salida.image' => 'La foto debe ser una imagen.',
            'foto_salida.max' => 'La foto no debe pesar mas de 2MB.',
        ];
    }

    /**
     * Reglas comunes de fecha, jornada y horas.
     *
     * En la edicion se pasa la asistencia actual para que la regla
     * anti-duplicados no choque con el propio registro que se esta corrigiendo.
     */
    protected function reglasComunes(?Asistencia $asistencia = null): array
    {
        return [
            'pasante_id' => ['required', 'integer', 'exists:pasantes,id'],
            'fecha' => ['required', 'date_format:Y-m-d', $this->reglaSinDuplicado($asistencia)],
            'jornada' => ['required', Rule::in(Jornada::valores())],
            'hora_entrada' => ['required', 'date_format:H:i'],
            'hora_salida' => ['nullable', 'date_format:H:i', 'after:hora_entrada'],
        ];
    }

    /**
     * Impide dos turnos del mismo pasante en la misma fecha y jornada.
     *
     * No se usa Rule::unique porque la columna `fecha` tiene cast 'date': el
     * valor guardado es 'Y-m-d' y una comparacion de texto contra la fecha del
     * formulario no lo encontraria. La base de datos tiene el indice
     * asistencias_turno_unico, asi que esta regla solo evita el error feo.
     */
    private function reglaSinDuplicado(?Asistencia $asistencia = null): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($asistencia) {
            $repetido = Asistencia::query()
                ->where('pasante_id', $this->input('pasante_id'))
                ->where('jornada', $this->input('jornada'))
                ->whereDate('fecha', $value)
                ->when($asistencia, fn ($query) => $query->whereKeyNot($asistencia->id))
                ->exists();

            if ($repetido) {
                $fail('Ese pasante ya tiene un registro para esa fecha y jornada.');
            }
        };
    }
}
