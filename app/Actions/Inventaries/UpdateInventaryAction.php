<?php

declare(strict_types=1);

namespace App\Actions\Inventaries;

use App\Data\Inventaries\UpdateInventaryData;
use App\Models\Inventary;
use App\Repositories\Interfaces\InventaryRepositoryExtendedInterface;

class UpdateInventaryAction
{
    public function __construct(
        private readonly InventaryRepositoryExtendedInterface $inventaryRepository,
    ) {}

    public function __invoke(Inventary $inventary, UpdateInventaryData $data): Inventary
    {
        $fields = array_filter($data->toArray(), fn($v) => $v !== null);

        return $this->inventaryRepository->update($inventary, $fields);
    }
}
