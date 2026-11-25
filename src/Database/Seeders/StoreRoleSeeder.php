<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Database\Seeders;

use Illuminate\Database\Seeder;
use SivarcartCore\SivarcartCore\Models\StoreRole;

/**
 * Seeds the roles the package ships with.
 */
class StoreRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            [
                'slug' => 'owner',
                'name' => 'Dueño',
                'description' => 'Control total de la tienda.',
                'permissions' => ['*'],
            ],
            [
                'slug' => 'manager',
                'name' => 'Administrador',
                'description' => 'Administra el catálogo, los pedidos y los pagos.',
                'permissions' => [
                    'catalog.*',
                    'orders.*',
                    'customers.*',
                    'payments.*',
                    'shipping.*',
                    'settings.view',
                ],
            ],
            [
                'slug' => 'staff',
                'name' => 'Empleado',
                'description' => 'Prepara los pedidos y los entrega.',
                'permissions' => [
                    'catalog.view',
                    'orders.view',
                    'orders.update',
                    'customers.view',
                ],
            ],
        ];

        foreach ($roles as $role) {
            StoreRole::query()->updateOrCreate(
                ['slug' => $role['slug']],
                [
                    'name' => $role['name'],
                    'description' => $role['description'],
                    'permissions' => $role['permissions'],
                    'is_system' => true,
                ],
            );
        }
    }
}
