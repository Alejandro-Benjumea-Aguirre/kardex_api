<?php

declare(strict_types=1);

namespace App\Http\Requests\Users;

class UpdateUserRequest extends \App\Http\Requests\ApiFormRequest
{
    public function rules(): array
    {
        // ─── CONCEPTO: ignore() en unique para updates ────────
        //
        // Al actualizar, el email debe ser único EXCEPTO para
        // el usuario que estamos editando. Sin ignore(), si
        // el usuario guarda sin cambiar el email, falla la
        // validación porque su propio email "ya existe".
        //
        // Route::current()->parameter('user') obtiene el ID
        // del parámetro de la ruta: /api/v1/users/{user}
        $userId = $this->route('user');

        return [
            'first_name' => ['sometimes', 'string', 'max:50'],
            'last_name'  => ['sometimes', 'string', 'max:50'],
            'phone'      => ['sometimes', 'nullable', 'string', 'max:20'],
            'avatar_url' => ['sometimes', 'nullable', 'url'],
            // Email: único en toda la tabla EXCEPTO este usuario
            'email'      => [
                'sometimes', 'string', 'email', 'max:254',
                \Illuminate\Validation\Rule::unique('users', 'email')->ignore($userId),
            ],
        ];
    }
}
