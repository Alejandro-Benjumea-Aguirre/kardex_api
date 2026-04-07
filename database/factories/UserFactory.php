<?php

namespace Database\Factories;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'company_id'        => Company::factory(),
            'first_name'        => fake()->firstName(),
            'last_name'         => fake()->lastName(),
            'email'             => fake()->unique()->safeEmail(),
            'password'          => static::$password ??= Hash::make('password'),
            'phone'             => fake()->optional()->phoneNumber(),
            'is_active'         => true,
            'is_email_verified' => true,
            'email_verified_at' => now(),
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }

    public function unverified(): static
    {
        return $this->state(fn () => [
            'is_email_verified' => false,
            'email_verified_at' => null,
        ]);
    }

    public function withoutCompany(): static
    {
        return $this->state(fn () => ['company_id' => null]);
    }
}
