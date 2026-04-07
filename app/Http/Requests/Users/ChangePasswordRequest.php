<?php

declare(strict_types=1);

namespace App\Http\Requests\Users;

class ChangePasswordRequest extends \App\Http\Requests\ApiFormRequest
{
    public function rules(): array
    {
        return [
            'current_password'      => ['required', 'string'],
            'password'              => ['required', 'string', 'min:8', 'confirmed'],
            'password_confirmation' => ['required'],
        ];
    }

    public function messages(): array
    {
        return [
            'password.confirmed' => 'Las contraseñas nuevas no coinciden.',
            'password.min'       => 'La nueva contraseña debe tener al menos 8 caracteres.',
        ];
    }
}
