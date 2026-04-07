<?php

declare(strict_types=1);

namespace App\Data\Users;

use Spatie\LaravelData\Data;

class ChangePasswordData extends Data
{
    public function __construct(
        public readonly string $current_password,
        public readonly string $password,
        public readonly string $password_confirmation,
    ) {}
}
