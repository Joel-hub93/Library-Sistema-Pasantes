<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTutorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'codigo_docente' => ['required', 'string', 'max:20', 'unique:tutores'],
            'nombres' => ['required', 'string', 'max:100'],
            'apellidos' => ['required', 'string', 'max:100'],
            'correo' => ['required', 'email', 'max:100'],
            'carrera_id' => ['required', 'exists:carreras,id'],
            'foto' => ['nullable', 'image', 'max:2048'],
        ];
    }
}
