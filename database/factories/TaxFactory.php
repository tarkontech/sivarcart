<?php

declare(strict_types=1);

namespace Tarkontech\SivarCart\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Tarkontech\SivarCart\Models\Tax;

/**
 * @extends Factory<Tax>
 */
class TaxFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<Tax>
     */
    protected $model = Tax::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'IVA 13%',
            'code' => 'IVA13-'.fake()->unique()->numerify('####'),
            'rate' => 0.13,
            'is_default' => true,
            'is_active' => true,
            'applies_to_shipping' => false,
        ];
    }

    /**
     * Create a tax category that is not taxed at all.
     */
    public function exempt(): static
    {
        return $this->state(fn (array $attributes): array => [
            'name' => 'Exento',
            'code' => 'EXENTO-'.fake()->unique()->numerify('####'),
            'rate' => 0,
            'is_default' => false,
        ]);
    }
}
