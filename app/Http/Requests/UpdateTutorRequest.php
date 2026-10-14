<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTutorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tutor = $this->route('tutor');

        return [
            'codigo_docente' => [
                'required',
                'string',
                'max:20',
                Rule::unique('tutores', 'codigo_docente')->ignore($tutor->id),
            ],
            'nombres' => ['required', 'string', 'max:100'],
            'apellidos' => ['required', 'string', 'max:100'],
            'correo' => ['required', 'email', 'max:100'],
            'carrera_id' => ['required', 'exists:carreras,id'],
            'estado' => ['required', 'in:0,1'],
            'foto' => ['nullable', 'image', 'max:2048'],
        ];
    }
}
