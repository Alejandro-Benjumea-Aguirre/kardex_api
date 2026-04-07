<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use App\Http\Requests\ApiFormRequest;

class ForgotPasswordRequest extends ApiFormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->email) {
            $this->merge(['email' => strtolower(trim($this->email))]);
        }
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email', 'max:254'],
        ];
    }

    // IMPORTANTE: Aquí no validamos si el email existe en la DB
    // porque eso revelaría qué emails están registrados.
    // La validación de existencia se hace en la Action,
    // y la respuesta es siempre la misma exista o no el email.
}
