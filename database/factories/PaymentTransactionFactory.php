<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use SivarcartCore\SivarcartCore\Enums\PaymentTransactionStatus;
use SivarcartCore\SivarcartCore\Models\PaymentTransaction;

/**
 * @extends Factory<PaymentTransaction>
 */
class PaymentTransactionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<PaymentTransaction>
     */
    protected $model = PaymentTransaction::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $transactionId = Str::upper(Str::random(24));

        return [
            'payment_id' => PaymentFactory::new(),
            'gateway' => 'wompi',
            'gateway_transaction_id' => $transactionId,
            'gateway_payment_attempt_id' => Str::upper(Str::random(16)),
            'idempotency_key' => $transactionId.':'.Str::upper(Str::random(16)),
            'amount' => fake()->randomFloat(2, 20, 400),
            'currency' => 'USD',
            'status' => PaymentTransactionStatus::APPROVED,
            'is_approved' => true,
            'is_production' => true,
            'result_code' => 1,
            'payment_method' => 'TarjetaCreditoDebito',
            'authorization_code' => Str::upper(Str::random(6)),
            'merchant_reference' => null,
            'occurred_at' => now(),
            'raw_payload' => [],
        ];
    }

    /**
     * Create a transaction that was rejected by the gateway.
     */
    public function rejected(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => PaymentTransactionStatus::REJECTED,
            'is_approved' => false,
            'result_code' => 2,
        ]);
    }

    /**
     * Create a transaction that was never charged to the customer.
     */
    public function testTransaction(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_production' => false,
        ]);
    }
}
