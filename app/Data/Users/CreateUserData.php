<?php

declare(strict_types=1);

namespace App\Data\Users;

use Spatie\LaravelData\Data;

class CreateUserData extends Data
{
    public function __construct(
        public readonly string  $first_name,
        public readonly string  $last_name,
        public readonly string  $email,
        public readonly string  $password,
        public readonly ?string $phone      = null,
        public readonly ?string $role_id    = null,
        public readonly ?string $branch_id  = null,
        public readonly ?string $company_id = null,
    ) {}
}
