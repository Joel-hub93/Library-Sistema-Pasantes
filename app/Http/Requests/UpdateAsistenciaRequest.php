<?php

namespace App\Http\Requests;

/**
 * Correccion de una asistencia existente.
 *
 * Solo se editan fecha, jornada y horas: las fotos del registro original se
 * conservan y no se pueden reemplazar desde aqui, por eso no hay reglas de
 * foto en este formulario.
 */
class UpdateAsistenciaRequest extends AsistenciaRequest
{
    public function rules(): array
    {
        return $this->reglasComunes($this->route('asistencia'));
    }
}
