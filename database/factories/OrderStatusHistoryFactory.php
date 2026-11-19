<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use SivarcartCore\SivarcartCore\Enums\OrderStatus;
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
            'status' => OrderStatus::PENDING,
            'previous_status' => null,
            'note' => null,
            'user_id' => null,
            'meta' => [],
        ];
    }
}
