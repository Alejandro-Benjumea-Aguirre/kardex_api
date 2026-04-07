<?php

declare(strict_types=1);

namespace App\Data\Auth;

use Spatie\LaravelData\Data;

// ─── RegisterData ───────────────────────────────────────────
//
// Spatie Data mapea automáticamente la clave "user" del request
// a la propiedad $user (RegisterUserData), porque el RegisterRequest
// usa reglas namespaceadas con "user.*".
//
// RegisterData::from($request) funciona directamente porque
// $request->all() devuelve ['user' => [...], 'company' => [...]].

class RegisterData extends Data
{
    public function __construct(
        public readonly RegisterUserData $user,
    ) {}
}
