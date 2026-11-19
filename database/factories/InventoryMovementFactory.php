<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use SivarcartCore\SivarcartCore\Enums\InventoryMovementType;
use SivarcartCore\SivarcartCore\Models\InventoryMovement;

/**
 * @extends Factory<InventoryMovement>
 */
class InventoryMovementFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<InventoryMovement>
     */
    protected $model = InventoryMovement::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_variant_id' => ProductVariantFactory::new(),
            'type' => InventoryMovementType::RESTOCK,
            'quantity' => 10,
            'stock_after' => 10,
            'reason' => fake()->sentence(),
            'reference_type' => null,
            'reference_id' => null,
            'user_id' => null,
            'meta' => [],
        ];
    }

    /**
     * Create a movement that sells stock.
     */
    public function sale(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => InventoryMovementType::SALE,
            'quantity' => -1,
        ]);
    }
}
