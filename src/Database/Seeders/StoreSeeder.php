<?php

declare(strict_types=1);

namespace Tarkontech\SivarCart\Database\Seeders;

use Illuminate\Database\Seeder;
use Tarkontech\SivarCart\Models\Department;
use Tarkontech\SivarCart\Models\Store;

/**
 * Seeds the profile of the single store this installation serves.
 */
class StoreSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $config = config('sivarcart.store');

        $department = Department::query()->where('code', 'SS')->first();

        Store::query()->updateOrCreate(
            ['slug' => $config['slug']],
            [
                'name' => $config['name'],
                'legal_name' => $config['legal_name'],
                'tax_id' => $config['tax_id'],
                'email' => $config['email'],
                'phone' => $config['phone'],
                'country_code' => 'SV',
                'phone_country_code' => '503',
                'currency' => $config['currency'],
                'timezone' => $config['timezone'],
                'locale' => $config['locale'],
                'department_id' => $department?->getKey(),
                'prices_include_tax' => $config['prices_include_tax'],
                'default_tax_rate' => $config['default_tax_rate'],
                'low_stock_threshold' => $config['low_stock_threshold'],
                'order_number_prefix' => $config['order_number_prefix'],
                'payment_number_prefix' => $config['payment_number_prefix'],
                'meta' => [],
                'is_active' => true,
            ],
        );
    }
}
