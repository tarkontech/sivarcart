<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use SivarcartCore\SivarcartCore\Models\StoreUser;

/**
 * @extends Factory<StoreUser>
 */
class StoreUserFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<StoreUser>
     */
    protected $model = StoreUser::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => Str::lower(Str::random(12)).'@example.test',
            'email_verified_at' => now(),
            'password' => 'password',
            'phone' => fake()->numerify('7#######'),
            'avatar_path' => null,
            'is_active' => true,
            'meta' => [],
        ];
    }

    /**
     * Indicate that the model is unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes): array => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Indicate that the member of the staff is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }

    /**
     * Give the member of the staff a specific email address.
     */
    public function withEmail(string $email): static
    {
        return $this->state(fn (array $attributes): array => [
            'email' => $email,
        ]);
    }
}
