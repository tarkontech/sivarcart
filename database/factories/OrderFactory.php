<?php

declare(strict_types=1);

namespace Tarkontech\SivarCart\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Tarkontech\SivarCart\Enums\OrderStatus;
use Tarkontech\SivarCart\Enums\PaymentStatus;
use Tarkontech\SivarCart\Models\Order;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<Order>
     */
    protected $model = Order::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $total = fake()->randomFloat(2, 20, 400);

        return [
            'order_number' => 'SIV-'.now()->year.'-'.Str::upper(Str::random(8)),
            'reference' => null,
            'customer_id' => null,
            'customer_name' => fake()->name(),
            'customer_email' => Str::lower(Str::random(12)).'@example.test',
            'customer_phone' => fake()->numerify('7#######'),
            'status' => OrderStatus::PENDING,
            'payment_status' => PaymentStatus::UNPAID,
            'currency' => 'USD',
            'subtotal' => $total,
            'discount_total' => 0,
            'tax_total' => 0,
            'shipping_total' => 0,
            'total' => $total,
            'tax_inclusive' => true,
            'coupon_code' => null,
            'shipping_address' => [
                'first_name' => fake()->firstName(),
                'last_name' => fake()->lastName(),
                'phone' => fake()->numerify('7#######'),
                'address_line1' => fake()->streetAddress(),
                'municipality' => fake()->city(),
                'department' => 'San Salvador',
            ],
            'billing_address' => null,
            'shipping_method_code' => 'standard',
            'shipping_method_name' => 'Envío estándar',
            'customer_note' => null,
            'placed_at' => now(),
            'paid_at' => null,
            'cancelled_at' => null,
            'fulfilled_at' => null,
            'meta' => [],
        ];
    }

    /**
     * Create an order that has already been paid.
     *
     * The payment is an axis of its own: a paid order that is not prepared yet
     * is still a pending order.
     */
    public function paid(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => OrderStatus::PENDING,
            'payment_status' => PaymentStatus::PAID,
            'paid_at' => now(),
        ]);
    }

    /**
     * Create an order placed by a guest.
     */
    public function guest(): static
    {
        return $this->state(fn (array $attributes): array => [
            'customer_id' => null,
        ]);
    }
}
