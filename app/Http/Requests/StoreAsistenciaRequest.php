<?php

namespace App\Http\Requests;

/**
 * Alta de una asistencia a mano.
 *
 * Las fotos son opcionales en la gestion administrativa, a diferencia del
 * flujo del pasante.
 */
class StoreAsistenciaRequest extends AsistenciaRequest
{
    public function rules(): array
    {
        return $this->reglasComunes() + [
            'foto_entrada' => ['nullable', 'image', 'max:2048'],
            'foto_salida' => ['nullable', 'image', 'max:2048'],
        ];
    }
}
