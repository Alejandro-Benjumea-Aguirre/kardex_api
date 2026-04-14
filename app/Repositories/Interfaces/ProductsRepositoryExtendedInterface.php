<?php

declare(strict_types=1);

namespace App\Repositories\Interfaces;

use App\Models\Products;
use Illuminate\Database\Eloquent\Collection;

// ═══════════════════════════════════════════════════════════
// UserRepositoryInterface

interface ProductsRepositoryExtendedInterface
{

    public function paginate(array $filters, int $perPage = 20): \Illuminate\Pagination\LengthAwarePaginator;
    public function findById(string $id): ?\App\Models\Products;
    public function findByCategory(string $categoryId): ?\App\Models\Products;
    public function create(array $data): \App\Models\Products;
    public function update(\App\Models\Products $product, array $data): \App\Models\Products;
    public function deactivate(\App\Models\Products; $product): void;
    public function activate(\App\Models\Products; $product): void;

}
