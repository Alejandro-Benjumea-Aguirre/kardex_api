<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ProductsFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->words(3, true);

        return [
            'company_id'         => Company::factory(),
            'category_id'        => null,
            'name'               => ucfirst($name),
            'slug'               => Str::slug($name) . '-' . fake()->unique()->numerify('###'),
            'sku'                => strtoupper(fake()->unique()->bothify('??-###')),
            'description'        => fake()->sentence(),
            'type'               => 'physical',
            'sale_price'         => fake()->randomFloat(2, 1, 500),
            'cost_price'         => fake()->randomFloat(2, 1, 200),
            'price_includes_tax' => false,
            'tax_rate'           => 19.00,
            'has_variants'       => false,
            'is_active'          => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
