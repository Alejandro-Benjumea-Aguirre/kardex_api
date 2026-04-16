<?php

declare(strict_types=1);

namespace App\Actions\Inventaries;

use App\Models\Inventary;
use App\Repositories\Interfaces\InventaryRepositoryExtendedInterface;

class DeactivateInventaryAction
{
    public function __construct(
        private readonly InventaryRepositoryExtendedInterface $inventaryRepository,
    ) {}

    public function __invoke(Inventary $inventary): void
    {
        $this->inventaryRepository->deactivate($inventary);
    }
}
