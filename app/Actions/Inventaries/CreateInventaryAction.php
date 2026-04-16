<?php

declare(strict_types=1);

namespace App\Actions\Inventaries;

use App\Data\Inventaries\CreateInventaryData;
use App\Models\Inventary;
use App\Repositories\Interfaces\InventaryRepositoryExtendedInterface;

class CreateInventaryAction
{
    public function __construct(
        private readonly InventaryRepositoryExtendedInterface $inventaryRepository,
    ) {}

    public function __invoke(CreateInventaryData $data): Inventary
    {
        return $this->inventaryRepository->create([
            'branch_id'          => $data->branch_id,
            'product_variant_id' => $data->product_variant_id,
            'quantity'           => $data->quantity,
            'min_stock'          => $data->min_stock,
            'max_stock'          => $data->max_stock,
            'location'           => $data->location,
            'avg_cost'           => $data->avg_cost,
        ]);
    }
}
