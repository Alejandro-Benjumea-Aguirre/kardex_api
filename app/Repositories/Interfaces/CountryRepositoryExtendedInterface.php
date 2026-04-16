<?php

declare(strict_types=1);

namespace App\Repositories\Interfaces;

use App\Models\Country;
use Illuminate\Database\Eloquent\Collection;

interface CountryRepositoryExtendedInterface
{

    public function paginate(array $filters, int $perPage = 20): \Illuminate\Pagination\LengthAwarePaginator;
    public function findById(string $id): ? \App\Models\Country;

}
