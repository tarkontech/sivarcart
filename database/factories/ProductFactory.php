<?php

declare(strict_types=1);

namespace Tarkontech\SivarCart\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Tarkontech\SivarCart\Enums\ProductStatus;
use Tarkontech\SivarCart\Enums\ProductType;
use Tarkontech\SivarCart\Models\Product;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<Product>
     */
    protected $model = Product::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = implode(' ', (array) fake()->words(3, true));

        return [
            'type' => ProductType::SIMPLE,
            'name' => $name,
            'slug' => Str::slug($name, '-').'-'.Str::lower(Str::random(6)),
            'short_description' => fake()->sentence(),
            'description' => fake()->paragraph(),
            'status' => ProductStatus::PUBLISHED,
            'is_featured' => false,
            'is_virtual' => false,
            'tax_id' => null,
            'brand_id' => null,
            'thumbnail_path' => null,
            'weight' => null,
            'meta' => [],
            'published_at' => now(),
        ];
    }

    /**
     * Configure the factory so a product always ends up with a sellable unit.
     *
     * The model creates the default variant of a simple product on its own,
     * so the factory only gives it a realistic price and stock.
     */
    public function configure(): static
    {
        return $this->afterCreating(function (Product $product): void {
            if ($product->type !== ProductType::SIMPLE) {
                return;
            }

            $variant = $product->defaultVariant();

            if ($variant === null) {
                return;
            }

            $variant->update([
                'price' => 25.50,
                'stock_quantity' => 25,
            ]);
        });
    }

    /**
     * Create a product that is sold in several variants.
     */
    public function variable(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => ProductType::VARIABLE,
        ]);
    }

    /**
     * Create a product that is still a draft.
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => ProductStatus::DRAFT,
            'published_at' => null,
        ]);
    }
}
