<?php

declare(strict_types=1);

namespace App\Data\Users;

use Spatie\LaravelData\Data;

class AssignRoleData extends Data
{
    public function __construct(
        public readonly string  $role_id,
        public readonly ?string $branch_id = null,
    ) {}
}
