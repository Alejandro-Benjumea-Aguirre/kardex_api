<?php

declare(strict_types=1);

namespace App\Data\Inventaries;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;

// ─── UpdateUserData ─────────────────────────────────────────

class UpdateInventaryData extends Data
{
    public function __construct(
        public readonly decimal|Optional          $quantity = new Optional(),
        public readonly decimal|Optional          $min_stock = new Optional(),
        public readonly decimal|Optional          $max_stock      = new Optional(),
        public readonly int|Optional              $location = new Optional(),
        public readonly decimal|Optional          $avg_cost = new Optional(),
    ) {}
}
