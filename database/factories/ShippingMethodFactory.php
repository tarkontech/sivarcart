<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use SivarcartCore\SivarcartCore\Enums\ShippingMethodType;
use SivarcartCore\SivarcartCore\Models\ShippingMethod;

/**
 * @extends Factory<ShippingMethod>
 */
class ShippingMethodFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<ShippingMethod>
     */
    protected $model = ShippingMethod::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => 'shipping-'.Str::lower(Str::random(8)),
            'name' => fake()->words(2, true),
            'type' => ShippingMethodType::FLAT_RATE,
            'price' => 5,
            'min_order_amount' => null,
            'max_order_amount' => null,
            'estimated_days' => 2,
            'instructions' => null,
            'is_active' => true,
            'position' => 0,
            'meta' => [],
        ];
    }
}
