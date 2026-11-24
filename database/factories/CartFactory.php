<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use SivarcartCore\SivarcartCore\Models\Cart;

/**
 * @extends Factory<Cart>
 */
class CartFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<Cart>
     */
    protected $model = Cart::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'customer_id' => null,
            'currency' => 'USD',
            'coupon_id' => null,
            'subtotal' => 0,
            'discount_total' => 0,
            'tax_total' => 0,
            'shipping_total' => 0,
            'total' => 0,
            'item_count' => 0,
            'expires_at' => now()->addDays(30),
            'converted_at' => null,
            'meta' => [],
        ];
    }
}
