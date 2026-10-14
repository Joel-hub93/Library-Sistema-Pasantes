<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUsuarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'rol'      => ['required', 'string', Rule::exists('roles', 'name')->where('guard_name', 'web')],
        ];
    }

    public function messages(): array
    {
        return [
            'rol.required' => 'Debe seleccionar un rol.',
            'rol.exists'   => 'El rol seleccionado no es valido.',
        ];
    }
}