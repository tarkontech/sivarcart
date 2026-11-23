<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

/**
 * Publishes the mailables of the package so the merchant can rewrite them.
 *
 * The templates are published and not the PHP classes: the views of the
 * namespace "sivarcart-core" are read from the application first, so a
 * published template wins over the one of the package without touching a
 * single line of code.
 */
class SivarcartCorePublishMailablesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sivarcart-core:publish-mailables
                            {--force : Overwrite the mailables that were already published}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Publish the SivarCart Core mailables so they can be rewritten';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->components->info('Publishing the SivarCart Core mailables...');

        Artisan::call('vendor:publish', [
            '--tag' => 'sivarcart-core-mailables',
            '--force' => (bool) $this->option('force'),
        ]);

        $this->components->info('The mailables are published. Edit them in the "resources/views/vendor/sivarcart-core/mail" directory.');

        return self::SUCCESS;
    }
}
