<?php

declare(strict_types=1);

namespace App\Actions\Branches;

use App\Data\Branches\CreateBranchData;
use App\Models\Branch;
use App\Repositories\Interfaces\BranchRepositoryExtendedInterface;

class CreateBranchAction
{
    public function __construct(
        private readonly BranchRepositoryExtendedInterface $branchRepository,
    ) {}

    public function __invoke(CreateBranchData $data): Branch
    {
        return $this->branchRepository->create([
            'company_id'      => $data->company_id,
            'name'            => $data->name,
            'code'            => $data->code,
            'address'         => $data->address,
            'city'            => $data->city,
            'state'           => $data->state,
            'country'         => $data->country,
            'latitude'        => $data->latitude,
            'longitude'       => $data->longitude,
            'phone'           => $data->phone,
            'email'           => $data->email,
            'settings'        => $data->settings,
            'is_active'       => $data->is_active,
            'is_main'         => $data->is_main,
        ]);
    }
}
