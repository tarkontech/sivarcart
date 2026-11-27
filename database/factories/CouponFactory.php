<?php

declare(strict_types=1);

namespace Tarkontech\SivarCart\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Tarkontech\SivarCart\Enums\CouponType;
use Tarkontech\SivarCart\Models\Coupon;

/**
 * @extends Factory<Coupon>
 */
class CouponFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<Coupon>
     */
    protected $model = Coupon::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => Str::upper(Str::random(10)),
            'name' => implode(' ', (array) fake()->words(2, true)),
            'type' => CouponType::FIXED,
            'value' => 5,
            'min_order_amount' => null,
            'max_discount_amount' => null,
            'usage_limit' => null,
            'used_count' => 0,
            'per_customer_limit' => null,
            'starts_at' => null,
            'expires_at' => null,
            'is_active' => true,
        ];
    }

    /**
     * Create a coupon that gives a percentage off.
     */
    public function percentage(float $percentage = 10): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => CouponType::PERCENTAGE,
            'value' => $percentage,
        ]);
    }

    /**
     * Create a coupon that has already expired.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes): array => [
            'expires_at' => now()->subDay(),
        ]);
    }
}
