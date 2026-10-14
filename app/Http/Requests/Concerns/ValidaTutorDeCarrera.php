<?php

namespace App\Http\Requests\Concerns;

use App\Models\Tutor;
use Closure;

/**
 * Regla compartida: el tutor elegido debe pertenecer a la carrera elegida.
 *
 * La usan StorePasanteRequest y UpdatePasanteRequest. La pertenencia no se
 * puede expresar solo con "exists", por eso se resuelve con una regla de
 * cierre sobre el propio request.
 */
trait ValidaTutorDeCarrera
{
    protected function tutorDeLaCarrera(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            $tutor = Tutor::find($value);

            // Si el tutor no existe lo reporta la regla 'exists'; aqui solo se
            // comprueba que su carrera coincida con la elegida.
            if ($tutor && (string) $tutor->carrera_id !== (string) $this->input('carrera_id')) {
                $fail('El tutor seleccionado no pertenece a la carrera elegida.');
            }
        };
    }
}
