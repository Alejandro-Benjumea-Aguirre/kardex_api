<?php

declare(strict_types=1);

namespace App\Data\Auth;

use Spatie\LaravelData\Data;

// ─── RegisterData ───────────────────────────────────────────

class RegisterData extends Data
{
    public function __construct(
        public readonly RegisterUserData $user,
    ) {}
}
