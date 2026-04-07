<?php

declare(strict_types=1);

namespace App\Http\Requests\Users;

class CreateUserRequest extends \App\Http\Requests\ApiFormRequest
{
    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:50'],
            'last_name'  => ['required', 'string', 'max:50'],
            'email'      => ['required', 'string', 'email', 'max:254', 'unique:users,email'],
            'password'   => ['required', 'string', 'min:8', 'confirmed'],
            'password_confirmation' => ['required'],
            'phone'      => ['nullable', 'string', 'max:20'],
            'role_id'    => ['nullable', 'uuid', 'exists:roles,id'],
            'branch_id'  => ['nullable', 'uuid', 'exists:branches,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique'     => 'Este email ya está registrado.',
            'password.min'     => 'La contraseña debe tener al menos 8 caracteres.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
            'role_id.exists'   => 'El rol seleccionado no existe.',
            'branch_id.exists' => 'La sucursal seleccionada no existe.',
        ];
    }
}
