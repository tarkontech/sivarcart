<?php

declare(strict_types=1);

namespace Tarkontech\SivarCart\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Tarkontech\SivarCart\Enums\ProductOptionType;
use Tarkontech\SivarCart\Models\ProductOption;

/**
 * @extends Factory<ProductOption>
 */
class ProductOptionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<ProductOption>
     */
    protected $model = ProductOption::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => ProductFactory::new()->variable(),
            'name' => fake()->randomElement(['Talla', 'Color']),
            'type' => ProductOptionType::TEXT,
            'position' => 0,
        ];
    }
}
