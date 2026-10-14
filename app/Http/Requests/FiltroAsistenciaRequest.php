<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Filtros del listado administrativo de asistencias.
 *
 * Ambos campos son opcionales: sin filtros se listan todas las asistencias.
 */
class FiltroAsistenciaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'pasante_id' => ['nullable', 'integer', 'exists:pasantes,id'],
            'fecha' => ['nullable', 'date_format:Y-m-d'],
        ];
    }
}
