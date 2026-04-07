<?php

declare(strict_types=1);

namespace App\Data\Auth;

use App\Models\User;
use Spatie\LaravelData\Data;

// ─── AuthResultData ─────────────────────────────────────────
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
