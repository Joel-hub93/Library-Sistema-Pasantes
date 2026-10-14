<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidaTutorDeCarrera;
use App\Models\Pasante;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePasanteRequest extends FormRequest
{
    use ValidaTutorDeCarrera;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var Pasante $pasante */
        $pasante = $this->route('pasante');

        return [
            'cedula'        => ['required', 'string', 'max:10', Rule::unique('pasantes', 'cedula')->ignore($pasante->id)],
            'nombres'       => ['required', 'string', 'max:100'],
            'apellidos'     => ['required', 'string', 'max:100'],
            'email'         => ['required', 'string', 'email', 'max:100', Rule::unique('pasantes', 'email')->ignore($pasante->id)],
            'carrera_id'    => ['required', 'integer', 'exists:carreras,id'],
            'telefono'      => ['required', 'string', 'max:15'],
            'fecha_ingreso' => ['required', 'date'],
            'tutor_id'      => ['required', 'integer', 'exists:tutores,id', $this->tutorDeLaCarrera()],
            'estado'        => ['required', 'in:0,1'],
            'foto'          => ['nullable', 'image', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'carrera_id.required' => 'Debe seleccionar una carrera.',
            'carrera_id.exists'   => 'La carrera seleccionada no es valida.',
            'tutor_id.required'   => 'Debe seleccionar un tutor.',
            'tutor_id.exists'     => 'El tutor seleccionado no es valido.',
        ];
    }
}
