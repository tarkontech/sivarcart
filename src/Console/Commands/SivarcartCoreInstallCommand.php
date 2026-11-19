<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use SivarcartCore\SivarcartCore\Database\Seeders\DatabaseSeeder;

/**
 * Installs SivarCart Core into the host application.
 *
 * The migrations are published instead of being loaded from the package, so
 * the merchant owns the schema and can extend it. Publishing the same tag
 * again never overwrites an already published file.
 */
class SivarcartCoreInstallCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sivarcart-core:install
                            {--force : Overwrite the migrations that were already published}
                            {--no-seed : Do not seed the base data}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Publish the SivarCart Core migrations, run them and seed the base data';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->components->info('Publishing the SivarCart Core migrations...');

        Artisan::call('vendor:publish', [
            '--tag' => 'sivarcart-core-migrations',
            '--force' => (bool) $this->option('force'),
        ]);

        $this->components->info('Running the migrations...');

        Artisan::call('migrate', [
            '--force' => true,
        ]);

        if (! $this->option('no-seed')) {
            $this->components->info('Seeding the base data...');

            Artisan::call('db:seed', [
                '--class' => DatabaseSeeder::class,
                '--force' => true,
            ]);
        }

        $this->components->info('SivarCart Core is installed.');

        return self::SUCCESS;
    }
}
