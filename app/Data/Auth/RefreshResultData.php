<?php

declare(strict_types=1);

namespace App\Data\Auth;

use Spatie\LaravelData\Data;

// ─── RefreshResultData ──────────────────────────────────────
class RefreshResultData extends Data
{
    public function __construct(
        public readonly string $access_token,
        public readonly string $refresh_token,
        public readonly string $token_type,
        public readonly int    $expires_in,
    ) {}
}
