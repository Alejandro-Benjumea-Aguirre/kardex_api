<?php

declare(strict_types=1);

namespace App\Actions\Branch;

use App\Models\Branch;
use App\Repositories\Interfaces\BranchRepositoryExtendedInterface;

class DeactivateBranchAction
{
    public function __construct(
        private readonly BranchRepositoryExtendedInterface $branchRepository,
    ) {}

    public function __invoke(Branch $branch): void
    {
        $this->branchRepository->deactivate($branch);
    }
}
