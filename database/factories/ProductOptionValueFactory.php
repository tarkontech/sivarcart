<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use SivarcartCore\SivarcartCore\Models\ProductOptionValue;

/**
 * @extends Factory<ProductOptionValue>
 */
class ProductOptionValueFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<ProductOptionValue>
     */
    protected $model = ProductOptionValue::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_option_id' => ProductOptionFactory::new(),
            'value' => fake()->randomElement(['S', 'M', 'L', 'Rojo', 'Azul', 'Negro']),
            'label' => null,
            'position' => 0,
            'meta' => [],
        ];
    }
}
