<?php

declare(strict_types=1);

namespace App\Repositories\Interfaces;

use App\Models\Inventary;
use Illuminate\Pagination\LengthAwarePaginator;

interface InventaryRepositoryExtendedInterface
{
    public function paginate(array $filters, int $perPage = 20): LengthAwarePaginator;
    public function findById(string $id): ?Inventary;

    public function create(array $data): Inventary;
    public function update(Inventary $inventary, array $data): Inventary;

    public function activate(Inventary $inventary): void;
    public function deactivate(Inventary $inventary): void;
}
