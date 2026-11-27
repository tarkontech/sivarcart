<?php

declare(strict_types=1);

namespace Tarkontech\SivarCart\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

/**
 * Publishes the mailables of the package so the merchant can rewrite them.
 *
 * The templates are published and not the PHP classes: the views of the
 * namespace "sivarcart" are read from the application first, so a
 * published template wins over the one of the package without touching a
 * single line of code.
 */
class SivarCartPublishMailablesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sivarcart:publish-mailables
                            {--force : Overwrite the mailables that were already published}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Publish the SivarCart mailables so they can be rewritten';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->components->info('Publishing the SivarCart mailables...');

        Artisan::call('vendor:publish', [
            '--tag' => 'sivarcart-mailables',
            '--force' => (bool) $this->option('force'),
        ]);

        $this->components->info('The mailables are published. Edit them in the "resources/views/vendor/sivarcart/mail" directory.');

        return self::SUCCESS;
    }
}
