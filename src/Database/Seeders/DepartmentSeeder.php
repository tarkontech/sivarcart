<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Database\Seeders;

use Illuminate\Database\Seeder;
use SivarcartCore\SivarcartCore\Models\Department;

/**
 * Seeds the fourteen departments of El Salvador.
 */
class DepartmentSeeder extends Seeder
{
    /**
     * The departments of El Salvador, ordered by their official code.
     *
     * @var list<array{code: string, name: string}>
     */
    public const array DEPARTMENTS = [['code' => 'AH', 'name' => 'Ahuachapán'],
        ['code' => 'CA', 'name' => 'Cabañas'],
        ['code' => 'CH', 'name' => 'Chalatenango'],
        ['code' => 'CU', 'name' => 'Cuscatlán'],
        ['code' => 'LI', 'name' => 'La Libertad'],
        ['code' => 'PA', 'name' => 'La Paz'],
        ['code' => 'UN', 'name' => 'La Unión'],
        ['code' => 'MZ', 'name' => 'Morazán'],
        ['code' => 'SA', 'name' => 'Santa Ana'],
        ['code' => 'SS', 'name' => 'San Salvador'],
        ['code' => 'SM', 'name' => 'San Miguel'],
        ['code' => 'ST', 'name' => 'San Vicente'],
        ['code' => 'SO', 'name' => 'Sonsonate'],
        ['code' => 'US', 'name' => 'Usulután'],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (self::DEPARTMENTS as $position => $department) {
            Department::query()->updateOrCreate(
                ['code' => $department['code']],
                [
                    'name' => $department['name'],
                    'country_code' => 'SV',
                    'position' => $position + 1,
                    'is_active' => true,
                ],
            );
        }
    }
}
