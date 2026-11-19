<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use SivarcartCore\SivarcartCore\Enums\PaymentRecordStatus;
use SivarcartCore\SivarcartCore\Models\Payment;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<Payment>
     */
    protected $model = Payment::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => OrderFactory::new(),
            'payment_method_id' => null,
            'method_code' => 'cash_on_delivery',
            'gateway' => null,
            'reference' => 'PAY-'.now()->year.'-'.Str::upper(Str::random(8)),
            'status' => PaymentRecordStatus::PENDING,
            'amount' => fake()->randomFloat(2, 20, 400),
            'currency' => 'USD',
            'gateway_reference' => null,
            'paid_at' => null,
            'failed_at' => null,
            'failure_reason' => null,
            'refunded_amount' => 0,
            'attempt_count' => 0,
            'meta' => [],
        ];
    }

    /**
     * Create a payment that has already been settled.
     */
    public function paid(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => PaymentRecordStatus::PAID,
            'paid_at' => now(),
        ]);
    }

    /**
     * Create a payment collected through the Wompi gateway.
     */
    public function wompi(): static
    {
        return $this->state(fn (array $attributes): array => [
            'method_code' => 'wompi',
            'gateway' => 'wompi',
        ]);
    }
}
