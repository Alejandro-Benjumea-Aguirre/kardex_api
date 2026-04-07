<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use App\Http\Requests\ApiFormRequest;

class RefreshTokenRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            // El refresh token viene en el body o como cookie HttpOnly.
            // Si viene como cookie, lo extraemos en el Controller.
            // Si viene en el body (para apps móviles), lo validamos aquí.
            'refresh_token' => ['required', 'string'],
        ];
    }
}
