<?php

namespace Database\Factories;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class BranchFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'name'       => fake()->city() . ' Branch',
            'code'       => strtoupper(Str::random(6)),
            'address'    => fake()->address(),
            'is_active'  => true,
            'is_main'    => false,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }

    public function main(): static
    {
        return $this->state(fn () => ['is_main' => true]);
    }
}
