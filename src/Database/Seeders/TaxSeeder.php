<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Database\Seeders;

use Illuminate\Database\Seeder;
use SivarcartCore\SivarcartCore\Models\Tax;

/**
 * Seeds the tax categories a Salvadoran store usually needs.
 */
class TaxSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $taxes = [
            [
                'code' => 'iva-13',
                'name' => 'IVA 13%',
                'rate' => 0.13,
                'is_default' => true,
                'is_active' => true,
                'applies_to_shipping' => false,
            ],
            [
                'code' => 'exento',
                'name' => 'Exento',
                'rate' => 0,
                'is_default' => false,
                'is_active' => true,
                'applies_to_shipping' => false,
            ],
            [
                'code' => 'sin-iva',
                'name' => 'Sin IVA',
                'rate' => 0,
                'is_default' => false,
                'is_active' => true,
                'applies_to_shipping' => false,
            ],
        ];

        foreach ($taxes as $tax) {
            Tax::query()->updateOrCreate(
                ['code' => $tax['code']],
                $tax,
            );
        }
    }
}
