<?php

declare(strict_types=1);

namespace Tarkontech\SivarCart\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Tarkontech\SivarCart\Enums\PaymentMethodType;
use Tarkontech\SivarCart\Models\PaymentMethod;

/**
 * @extends Factory<PaymentMethod>
 */
class PaymentMethodFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<PaymentMethod>
     */
    protected $model = PaymentMethod::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => 'method-'.Str::lower(Str::random(8)),
            'name' => fake()->words(2, true),
            'type' => PaymentMethodType::OFFLINE,
            'driver' => null,
            'credentials' => null,
            'instructions' => null,
            'icon' => null,
            'is_active' => true,
            'position' => 0,
            'meta' => [],
        ];
    }

    /**
     * Create the Wompi gateway, without credentials.
     */
    public function wompi(): static
    {
        return $this->state(fn (array $attributes): array => [
            'code' => 'wompi',
            'name' => 'Wompi',
            'type' => PaymentMethodType::ONLINE,
            'driver' => 'wompi',
        ]);
    }

    /**
     * Create the cash on delivery method.
     */
    public function cashOnDelivery(): static
    {
        return $this->state(fn (array $attributes): array => [
            'code' => 'cash_on_delivery',
            'name' => 'Contra entrega',
            'type' => PaymentMethodType::OFFLINE,
            'driver' => null,
            'instructions' => 'Pague en efectivo al recibir su pedido.',
        ]);
    }
}
