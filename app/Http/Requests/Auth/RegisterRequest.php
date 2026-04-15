<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use App\Http\Requests\ApiFormRequest;

class RegisterRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            // ─── Empresa ─────────────────────────────────
            'company.name'     => ['required', 'string', 'max:100'],
            'company.slug'     => ['nullable', 'string', 'max:100',
                                    'unique:companies,slug'],
            'company.plan'     => ['nullable', Rule::in([
                                    'free', 'starter',
                                    'professional', 'enterprise'
                                  ])],
            'company.logo_url' => ['nullable', 'url'],

            // ─── Usuario admin ────────────────────────────
            'user.first_name'        => ['required', 'string', 'max:100'],
            'user.last_name'        => ['required', 'string', 'max:100'],
            'user.email'       => ['required', 'email', 'unique:users,email'],
            'user.password'    => ['required', 'confirmed', Password::min(8)
                                    ->mixedCase()
                                    ->numbers()],
        ];
    }

    public function messages(): array
    {
        return [
            // Empresa
            'company.name.required'  => 'El nombre de la empresa es obligatorio.',
            'company.slug.unique'    => 'Este slug ya está en uso.',

            // Usuario
            'user.first_name.required'  => 'El nombre del usuario es obligatorio.',
            'user.last_name.required'   => 'El nombre del usuario es obligatorio.',
            'user.email.required'       => 'El correo electrónico es obligatorio.',
            'user.email.unique'         => 'Este correo ya está registrado.',
            'user.password.required'    => 'La contraseña es obligatoria.',
            'user.password.confirmed'   => 'Las contraseñas no coinciden.',
        ];
    }
}
