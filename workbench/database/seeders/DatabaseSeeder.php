<?php

declare(strict_types=1);

namespace Workbench\Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use SivarcartCore\SivarcartCore\Database\Seeders\DatabaseSeeder as SivarcartCoreSeeder;
use Workbench\Database\Factories\UserFactory;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // UserFactory::new()->times(10)->create();

        $this->call(SivarcartCoreSeeder::class);

        $user = UserFactory::new()->createOne([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        // The user of the workbench is the owner of the store, so it can open
        // every screen of the administration panel while developing.
        $user->assignStoreRole('owner');
    }
}
