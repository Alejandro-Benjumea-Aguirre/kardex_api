<?php

declare(strict_types=1);

namespace App\Repositories\Interfaces;

use App\Models\City;
use Illuminate\Database\Eloquent\Collection;

interface CityRepositoryExtendedInterface
{

    public function paginate(array $filters, int $perPage = 20): \Illuminate\Pagination\LengthAwarePaginator;
    public function findById(string $id): ? \App\Models\City;

}
