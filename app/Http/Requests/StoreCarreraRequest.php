<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCarreraRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'codigo_carrera' => ['required', 'string', 'max:20', 'unique:carreras,codigo_carrera'],
            'nombre' => ['required', 'string', 'max:100'],
            'horas_practicas' => ['required', 'integer', 'min:0'],
        ];
    }
}
