<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateAccesosUsuarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'roles'                  => ['nullable', 'array', 'max:2'],
            'roles.*'                => ['string', Rule::exists('roles', 'name')->where('guard_name', 'web'), 'distinct'],
            'direct_permissions'     => ['nullable', 'array'],
            'direct_permissions.*'   => ['string', Rule::exists('permissions', 'name')->where('guard_name', 'web'), 'distinct'],
        ];
    }

    public function messages(): array
    {
        return [
            'roles.max'      => 'No puede tener mas de 2 roles asignados.',
            'roles.*.exists' => 'Uno de los roles seleccionados no es valido.',
            'direct_permissions.*.exists' => 'Uno de los permisos seleccionados no es valido.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            /** @var User $user */
            $user = $this->route('user');
            $roles = $this->input('roles', []);

            if (! is_array($roles)) {
                return;
            }

            if (
                $user->id === $this->user()?->id
                && $user->hasRole('admin')
                && ! in_array('admin', $roles, true)
            ) {
                $validator->errors()->add(
                    'roles',
                    'No puedes quitarte a ti mismo el rol admin.'
                );
            }
        });
    }
}