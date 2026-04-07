<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;


class ResetPasswordRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'token'                 => ['required', 'string'],
            'password'              => ['required', 'string', 'min:8', 'confirmed'],
            'password_confirmation' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'token.required'     => 'El token de recuperación es obligatorio.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
            'password.min'       => 'La contraseña debe tener al menos 8 caracteres.',
        ];
    }
}
