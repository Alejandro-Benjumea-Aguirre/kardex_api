<?php

declare(strict_types=1);

namespace App\Data\Auth;

use Spatie\LaravelData\Data;

class ForgotPasswordData extends Data
{
    public function __construct(
        public readonly string $email,
    ) {}
}
