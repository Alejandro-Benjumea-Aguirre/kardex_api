<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Company>
 */
class CompanyFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->company();

        return [
            'name'      => $name,
            'nit'       => fake()->unique()->numerify('##########-#'),
            'sector'    => fake()->numberBetween(1, 10),
            'phone'     => fake()->numerify('###########'),
            'address'   => fake()->streetAddress(),
            'slug'      => Str::slug($name) . '-' . fake()->unique()->numerify('###'),
            'is_active' => true,
        ];
    }
}
