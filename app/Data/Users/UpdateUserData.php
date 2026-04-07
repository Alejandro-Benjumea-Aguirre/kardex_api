<?php

declare(strict_types=1);

namespace App\Data\Users;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;

// ─── UpdateUserData ─────────────────────────────────────────

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
