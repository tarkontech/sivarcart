<?php

declare(strict_types=1);

namespace Tarkontech\SivarCart\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Tarkontech\SivarCart\Models\StoreRole;

/**
 * @extends Factory<StoreRole>
 */
class StoreRoleFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<StoreRole>
     */
    protected $model = StoreRole::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->jobTitle();

        return [
            'name' => $name,
            'slug' => Str::slug($name, '-').'-'.Str::lower(Str::random(6)),
            'description' => fake()->sentence(),
            'permissions' => [],
            'is_system' => false,
        ];
    }

    /**
     * Indicate that the role is protected by the package.
     */
    public function system(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_system' => true,
        ]);
    }
}
