<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use App\Http\Requests\ApiFormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            // ─── Empresa ─────────────────────────────────
            'company.name'       => ['required', 'string', 'max:100'],
            'company.nit'        => ['required', 'string', 'max:50'],
            'company.sector'     => ['required', 'string', 'max:100'],
            'company.phone'      => ['required', 'string', 'max:30'],
            'company.address'    => ['required', 'string', 'max:255'],
            'company.city_id'    => ['nullable', 'uuid', 'exists:cities,id'],
            'company.country_id' => ['nullable', 'uuid', 'exists:countries,id'],
            'company.website'    => ['nullable', 'url', 'max:255'],
            'company.slug'       => ['nullable', 'string', 'max:100', 'unique:companies,slug'],
            'company.plan'       => ['nullable', Rule::in(['free', 'starter', 'professional', 'enterprise'])],
            'company.logo_url'   => ['nullable', 'url'],

            // ─── Usuario admin ────────────────────────────
            'user.first_name'             => ['required', 'string', 'max:100'],
            'user.last_name'              => ['required', 'string', 'max:100'],
            'user.email'                  => ['required', 'email', 'unique:users,email'],
            'user.password'               => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()],
            'user.password_confirmation'  => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            // Empresa
            'company.name.required'    => 'El nombre de la empresa es obligatorio.',
            'company.nit.required'     => 'El NIT / RUC es obligatorio.',
            'company.sector.required'  => 'El sector es obligatorio.',
            'company.phone.required'   => 'El teléfono es obligatorio.',
            'company.address.required' => 'La dirección es obligatoria.',
            'company.slug.unique'      => 'Este slug ya está en uso.',

            // Usuario
            'user.first_name.required' => 'El nombre del usuario es obligatorio.',
            'user.last_name.required'  => 'El apellido del usuario es obligatorio.',
            'user.email.required'      => 'El correo electrónico es obligatorio.',
            'user.email.unique'        => 'Este correo ya está registrado.',
            'user.password.required'   => 'La contraseña es obligatoria.',
            'user.password.confirmed'  => 'Las contraseñas no coinciden.',
        ];
    }
}
