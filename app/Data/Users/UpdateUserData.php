<?php

declare(strict_types=1);

namespace App\Data\Users;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;

// ─── UpdateUserData ─────────────────────────────────────────
//
// Todos los campos son Optional porque el endpoint usa PATCH/PUT
// parcial: el cliente puede enviar solo los campos que cambia.
//
// Optional vs nullable:
//   - Optional → el campo NO vino en el request (no tocarlo en la DB)
//   - null      → el campo vino explícitamente como null (borrarlo)
//   - string    → el campo vino con un valor (actualizarlo)
//
// toArray() de Spatie Data EXCLUYE automáticamente los Optional,
// por eso en UpdateUserAction hacemos $data->toArray() para obtener
// solo los campos que realmente vienen en el request.

class UpdateUserData extends Data
{
    public function __construct(
        public readonly string|Optional          $first_name = new Optional(),
        public readonly string|Optional          $last_name  = new Optional(),
        public readonly string|null|Optional     $phone      = new Optional(),
        public readonly string|null|Optional     $avatar_url = new Optional(),
        public readonly string|Optional          $email      = new Optional(),
    ) {}
}
