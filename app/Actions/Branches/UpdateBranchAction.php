<?php

declare(strict_types=1);

namespace App\Actions\Branches;

use App\Data\Branches\UpdateBranchData;
use App\Models\Branch;
use App\Repositories\Interfaces\BranchRepositoryExtendedInterface;

class UpdateBranchAction
{
    public function __construct(
        private readonly BranchRepositoryExtendedInterface $branchRepository,
    ) {}

    public function __invoke(Branch $branch, UpdateBranchData $data): Branch
    {
        $fields = array_filter($data->toArray(), fn($v) => $v !== null);

        return $this->branchRepository->update($branch, $fields);
    }
}
