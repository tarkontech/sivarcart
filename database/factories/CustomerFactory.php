<?php

declare(strict_types=1);

namespace Tarkontech\SivarCart\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Tarkontech\SivarCart\Models\Customer;

/**
 * @extends Factory<Customer>
 */
class CustomerFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<Customer>
     */
    protected $model = Customer::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => Str::lower(Str::random(12)).'@example.test',
            'phone' => fake()->numerify('7#######'),
            'document_type' => null,
            'document_number' => null,
            'password' => 'password',
            'email_verified_at' => now(),
            'phone_verified_at' => null,
            'accepts_marketing' => false,
            'is_active' => true,
            'notes' => null,
            'meta' => [],
        ];
    }

    /**
     * Create a guest customer, without email address and without password.
     */
    public function guest(): static
    {
        return $this->state(fn (array $attributes): array => [
            'email' => null,
            'password' => null,
        ]);
    }

    /**
     * Give the customer a specific email address.
     */
    public function withEmail(string $email): static
    {
        return $this->state(fn (array $attributes): array => [
            'email' => $email,
        ]);
    }
}
