<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use SivarcartCore\SivarcartCore\Models\CartItem;

/**
 * @extends Factory<CartItem>
 */
class CartItemFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<CartItem>
     */
    protected $model = CartItem::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $unitPrice = fake()->randomFloat(2, 5, 100);

        return [
            'cart_id' => CartFactory::new(),
            'product_id' => null,
            'product_variant_id' => null,
            'name' => implode(' ', (array) fake()->words(3, true)),
            'sku' => fake()->bothify('SKU-####'),
            'options' => [],
            'unit_price' => $unitPrice,
            'quantity' => 1,
            'line_subtotal' => $unitPrice,
            'discount_total' => 0,
            'tax_total' => 0,
            'line_total' => $unitPrice,
        ];
    }
}
