<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use SivarcartCore\SivarcartCore\Enums\OrderHistoryAxis;
use SivarcartCore\SivarcartCore\Enums\OrderStatus;
use SivarcartCore\SivarcartCore\Enums\PaymentStatus;
use SivarcartCore\SivarcartCore\Models\OrderStatusHistory;

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
