<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use SivarcartCore\SivarcartCore\Models\ProductImage;

/**
 * @extends Factory<ProductImage>
 */
class ProductImageFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<ProductImage>
     */
    protected $model = ProductImage::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => ProductFactory::new(),
            'product_variant_id' => null,
            'path' => 'products/'.fake()->lexify('????').'.jpg',
            'disk' => 'public',
            'alt' => fake()->words(3, true),
            'position' => 0,
            'is_primary' => false,
        ];
    }
}
