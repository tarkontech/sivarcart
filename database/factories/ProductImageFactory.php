<?php

declare(strict_types=1);

namespace Tarkontech\SivarCart\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Tarkontech\SivarCart\Models\Product;
use Tarkontech\SivarCart\Models\ProductImage;

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
        $fileName = fake()->lexify('????').'.jpg';

        return [
            'product_id' => ProductFactory::new(),
            'product_variant_id' => null,
            'path' => 'products/'.$fileName,
            'disk' => 'public',
            'alt' => fake()->words(3, true),
            'position' => 0,
            'is_primary' => false,
            'model_type' => Product::class,
            'collection_name' => 'images',
            'name' => pathinfo($fileName, PATHINFO_FILENAME),
            'file_name' => $fileName,
            'mime_type' => 'image/jpeg',
            'size' => 0,
            'manipulations' => [],
            'custom_properties' => [],
            'generated_conversions' => [],
            'responsive_images' => [],
        ];
    }

    /**
     * The image belongs to the product that owns it.
     */
    public function configure(): static
    {
        return $this->afterMaking(function (ProductImage $image): void {
            $image->model_id ??= $image->product_id;
        });
    }
}
