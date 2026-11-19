<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use SivarcartCore\SivarcartCore\Models\OrderItem;

/**
 * @extends Factory<OrderItem>
 */
class OrderItemFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<OrderItem>
     */
    protected $model = OrderItem::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $unitPrice = fake()->randomFloat(2, 5, 100);
        $quantity = fake()->numberBetween(1, 3);
        $lineSubtotal = round($unitPrice * $quantity, 2);

        return [
            'order_id' => OrderFactory::new(),
            'product_id' => null,
            'product_variant_id' => null,
            'name' => implode(' ', (array) fake()->words(3, true)),
            'sku' => fake()->bothify('SKU-####'),
            'variant_options' => [],
            'unit_price' => $unitPrice,
            'quantity' => $quantity,
            'line_subtotal' => $lineSubtotal,
            'discount_total' => 0,
            'tax_rate' => 0.13,
            'tax_total' => 0,
            'line_total' => $lineSubtotal,
        ];
    }
}
