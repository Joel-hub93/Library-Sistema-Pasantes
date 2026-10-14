<?php

namespace App\Http\Requests;

use App\Enums\Jornada;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Marcado publico del pasante (entrada o salida).
 *
 * La foto siempre es obligatoria: es la prueba del registro. La fecha y la
 * hora no se validan porque las pone el servidor, no el cliente.
 */
class MarcarAsistenciaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tipo' => ['required', Rule::in(['entrada', 'salida'])],
            'jornada' => ['required', Rule::in(Jornada::valores())],
            'foto' => ['required', 'image', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'tipo.required' => 'Selecciona si vas a marcar la entrada o la salida.',
            'tipo.in' => 'La opcion seleccionada no es valida.',
            'jornada.required' => 'Selecciona la jornada a la que perteneces.',
            'jornada.in' => 'La jornada seleccionada no es valida.',
            'foto.required' => 'Toma una foto para registrar la asistencia.',
            'foto.image' => 'La foto debe ser una imagen.',
            'foto.max' => 'La foto no debe pesar mas de 2MB.',
        ];
    }
}
