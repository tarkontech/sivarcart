<?php

declare(strict_types=1);

namespace Tarkontech\SivarCart\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Tarkontech\SivarCart\Models\ProductVariant;

/**
 * @extends Factory<ProductVariant>
 */
class ProductVariantFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<ProductVariant>
     */
    protected $model = ProductVariant::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => ProductFactory::new()->variable(),
            'sku' => Str::upper(Str::random(10)),
            'name' => null,
            'price' => fake()->randomFloat(2, 5, 500),
            'compare_at_price' => null,
            'cost_price' => null,
            'stock_quantity' => 25,
            'reserved_quantity' => 0,
            'low_stock_threshold' => 5,
            'weight' => null,
            'barcode' => null,
            'is_default' => false,
            'is_active' => true,
            'position' => 0,
            'image_path' => null,
            'meta' => [],
        ];
    }

    /**
     * Create a variant that is out of stock.
     */
    public function outOfStock(): static
    {
        return $this->state(fn (array $attributes): array => [
            'stock_quantity' => 0,
        ]);
    }

    /**
     * Create a variant that is the default one of its product.
     */
    public function default(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_default' => true,
        ]);
    }
}
