<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCarreraRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $carrera = $this->route('carrera');

        return [
            'codigo_carrera' => [
                'required',
                'string',
                'max:20',
                Rule::unique('carreras', 'codigo_carrera')->ignore($carrera->id),
            ],
            'nombre' => ['required', 'string', 'max:100'],
            'horas_practicas' => ['required', 'integer', 'min:0'],
            'estado' => ['required', 'in:0,1'],
        ];
    }
}
