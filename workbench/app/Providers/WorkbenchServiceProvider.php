<?php

declare(strict_types=1);

namespace Workbench\App\Providers;

use Illuminate\Support\ServiceProvider;

class WorkbenchServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Testbench falls back to an in-memory SQLite database when the SQLite
        // file does not exist yet. The workbench needs the file backed
        // database, otherwise the schema created by "composer build" would be
        // lost as soon as the command finishes and "composer serve" would
        // start without a database.
        config(['database.default' => 'sqlite']);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
