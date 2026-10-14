<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'        => ['required', 'string', 'max:50', 'unique:roles,name'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', Rule::exists('permissions', 'name')->where('guard_name', 'web'), 'distinct'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Debe ingresar un nombre para el rol.',
            'name.unique'   => 'Ya existe un rol con ese nombre.',
        ];
    }

    /**
     * Normaliza el nombre ANTES de validar para que la regla unique
     * compare contra el valor que realmente se guardara.
     */
    protected function prepareForValidation(): void
    {
        if ($this->filled('name')) {
            $this->merge([
                'name' => Str::of($this->input('name'))->trim()->lower()->value(),
            ]);
        }
    }
}