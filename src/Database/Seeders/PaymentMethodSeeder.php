<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Database\Seeders;

use Illuminate\Database\Seeder;
use SivarcartCore\SivarcartCore\Enums\PaymentMethodType;
use SivarcartCore\SivarcartCore\Models\PaymentMethod;

/**
 * Seeds the payment methods a Salvadoran store usually offers.
 *
 * The Wompi gateway is seeded inactive: it only becomes usable once the
 * merchant fills in its credentials from the administration panel.
 */
class PaymentMethodSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $methods = [
            [
                'code' => 'cash_on_delivery',
                'name' => 'Contra entrega',
                'type' => PaymentMethodType::OFFLINE,
                'driver' => null,
                'credentials' => null,
                'instructions' => 'Pague en efectivo al recibir su pedido.',
                'icon' => null,
                'is_active' => true,
                'position' => 1,
            ],
            [
                'code' => 'bank_transfer',
                'name' => 'Transferencia bancaria',
                'type' => PaymentMethodType::OFFLINE,
                'driver' => null,
                'credentials' => null,
                'instructions' => 'Realice la transferencia y envíenos el comprobante.',
                'icon' => null,
                'is_active' => true,
                'position' => 2,
            ],
            [
                'code' => 'wompi',
                'name' => 'Wompi',
                'type' => PaymentMethodType::ONLINE,
                'driver' => 'wompi',
                'credentials' => null,
                'instructions' => null,
                'icon' => null,
                'is_active' => false,
                'position' => 3,
            ],
        ];

        foreach ($methods as $method) {
            PaymentMethod::query()->updateOrCreate(
                ['code' => $method['code']],
                $method,
            );
        }
    }
}
