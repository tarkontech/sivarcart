<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Seeds the base data a store needs to start selling.
 */
class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call([
            DepartmentSeeder::class,
            TaxSeeder::class,
            StoreRoleSeeder::class,
            StoreSeeder::class,
            PaymentMethodSeeder::class,
            ShippingMethodSeeder::class,
        ]);
    }
}
