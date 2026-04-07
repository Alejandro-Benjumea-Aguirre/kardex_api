<?php

declare(strict_types=1);

namespace App\Data\Auth;

use App\Models\User;
use Spatie\LaravelData\Data;

// ─── AuthResultData ─────────────────────────────────────────
//
// DTO de salida del LoginAction. Reemplaza el array plano
// que devolvía antes, dando tipos explícitos a cada campo.
//
// El $user viene cargado con ['company', 'roles'] desde la Action.
// El $refresh_token se pone en cookie HttpOnly en el Controller
// y NO se incluye en el body de la respuesta.

class AuthResultData extends Data
{
    public function __construct(
        public readonly User   $user,
        public readonly string $access_token,
        public readonly string $refresh_token,
        public readonly string $token_type,
        public readonly int    $expires_in,
    ) {}
}
