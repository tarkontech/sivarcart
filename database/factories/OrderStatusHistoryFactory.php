<?php

declare(strict_types=1);

namespace Tarkontech\SivarCart\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Tarkontech\SivarCart\Enums\OrderHistoryAxis;
use Tarkontech\SivarCart\Enums\OrderStatus;
use Tarkontech\SivarCart\Enums\PaymentStatus;
use Tarkontech\SivarCart\Models\OrderStatusHistory;

/**
 * @extends Factory<OrderStatusHistory>
 */
class OrderStatusHistoryFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<OrderStatusHistory>
     */
    protected $model = OrderStatusHistory::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => OrderFactory::new(),
            'axis' => OrderHistoryAxis::STATUS,
            'status' => OrderStatus::PENDING,
            'previous_status' => null,
            'payment_status' => null,
            'previous_payment_status' => null,
            'note' => null,
            'user_id' => null,
            'meta' => [],
        ];
    }

    /**
     * Create a change of the state of the payment.
     */
    public function payment(): static
    {
        return $this->state(fn (array $attributes): array => [
            'axis' => OrderHistoryAxis::PAYMENT,
            'status' => null,
            'previous_status' => null,
            'payment_status' => PaymentStatus::PAID,
            'previous_payment_status' => PaymentStatus::UNPAID,
        ]);
    }
}
