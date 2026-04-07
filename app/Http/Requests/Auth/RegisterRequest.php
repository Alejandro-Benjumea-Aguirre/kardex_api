<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use App\Http\Requests\ApiFormRequest;

class RegisterRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'user.first_name'            => ['required', 'string', 'max:50'],
            'user.last_name'             => ['required', 'string', 'max:50'],
            'user.email'                 => ['required', 'string', 'email', 'max:254', 'unique:users,email'],
            'user.password'              => ['required', 'string', 'min:8'],
            'user.password_confirmation' => ['required', 'string', 'same:user.password'],
            'user.role'                  => ['nullable', 'string'],
            'company'                    => ['nullable', 'array'],
            'company.name'               => ['nullable', 'string', 'max:100'],
            'company.nit'                => ['nullable', 'string', 'max:20'],
        ];
    }

    public function messages(): array
    {
        return [
            'user.first_name.required'            => 'El nombre es obligatorio.',
            'user.last_name.required'             => 'El apellido es obligatorio.',
            'user.email.required'                 => 'El email es obligatorio.',
            'user.email.unique'                   => 'Este email ya está registrado.',
            'user.password.required'              => 'La contraseña es obligatoria.',
            'user.password.min'                   => 'La contraseña debe tener al menos 8 caracteres.',
            'user.password_confirmation.same'     => 'Las contraseñas no coinciden.',
        ];
    }
}
