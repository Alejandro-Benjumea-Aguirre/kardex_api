<?php

declare(strict_types=1);

// ═══════════════════════════════════════════════════════════
// INTERFACES
// ═══════════════════════════════════════════════════════════

namespace App\Repositories\Interfaces;

use App\Models\{Role, Permission, User};
use Illuminate\Database\Eloquent\Collection;

interface PermissionRepositoryInterface
{
    public function allActive(?string $companyId = null): Collection;
    public function allGroupedByModule(?string $companyId = null): array;
    public function findById(string $id): ?Permission;
    public function findByName(string $name): ?Permission;
    public function findByNames(array $names): Collection;
}
