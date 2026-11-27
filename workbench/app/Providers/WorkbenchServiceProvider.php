<?php

declare(strict_types=1);

namespace Workbench\App\Providers;

use Illuminate\Support\ServiceProvider;
use Workbench\App\Models\User;

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

        // The workbench plays the role of the host application, so the staff
        // of the store are its own users. SivarCart reads the model of
        // the "users" provider of "config/auth.php" to relate them.
        config(['auth.providers.users.model' => User::class]);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
