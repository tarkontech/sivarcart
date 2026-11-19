<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Database\Seeders;

use Illuminate\Database\Seeder;
use SivarcartCore\SivarcartCore\Enums\ShippingMethodType;
use SivarcartCore\SivarcartCore\Models\ShippingMethod;

/**
 * Seeds the shipping methods a Salvadoran store usually offers.
 */
class ShippingMethodSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $methods = [
            [
                'code' => 'standard',
                'name' => 'Envío estándar',
                'type' => ShippingMethodType::FLAT_RATE,
                'price' => 5,
                'min_order_amount' => null,
                'max_order_amount' => null,
                'estimated_days' => 2,
                'instructions' => 'Entregas en todo El Salvador.',
                'is_active' => true,
                'position' => 1,
            ],
            [
                'code' => 'pickup',
                'name' => 'Recoger en tienda',
                'type' => ShippingMethodType::PICKUP,
                'price' => 0,
                'min_order_amount' => null,
                'max_order_amount' => null,
                'estimated_days' => 0,
                'instructions' => 'Recoge tu pedido en el punto que te indicaríamos.',
                'is_active' => true,
                'position' => 2,
            ],
        ];

        foreach ($methods as $method) {
            ShippingMethod::query()->updateOrCreate(
                ['code' => $method['code']],
                $method,
            );
        }
    }
}
