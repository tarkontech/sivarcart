<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore;

use Illuminate\Support\ServiceProvider;
use SivarcartCore\SivarcartCore\Console\Commands\SivarcartCoreInstallCommand;

class SivarcartCoreServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/sivarcart-core.php', 'sivarcart-core');

        $this->app->singleton(SivarcartCore::class);
    }

    /**
     * Bootstrap any application services.
     *
     * The migrations of this package are published, never loaded from the
     * package itself. Laravel resolves the class of a migration by stripping
     * its timestamp, so a published copy and a package copy would end up
     * declaring the same class and "php artisan migrate" would fail with
     * "Cannot declare class". Publishing keeps the merchant in charge of the
     * schema, and "vendor:publish" never overwrites a file that already
     * exists, so a customisation survives an upgrade.
     *
     * The migrations are published with "publishes" instead of
     * "publishesMigrations" on purpose: the latter dates every published
     * migration with the current date, so publishing twice would leave two
     * copies of the same migration behind and "php artisan migrate" would
     * fail with "table already exists". Keeping the original names makes a
     * second publish skip the files that are already there and only add the
     * new ones.
     */
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/sivarcart-core.php');

        $this->loadViewsFrom(__DIR__.'/../resources/views', 'sivarcart-core');

        $this->loadTranslationsFrom(__DIR__.'/../lang', 'sivarcart-core');

        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->publishes([
            __DIR__.'/../config/sivarcart-core.php' => config_path('sivarcart-core.php'),
        ], ['sivarcart-core', 'sivarcart-core-config']);

        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/sivarcart-core'),
        ], ['sivarcart-core', 'sivarcart-core-views']);

        $this->publishes([
            __DIR__.'/../lang' => $this->app->langPath('vendor/sivarcart-core'),
        ], ['sivarcart-core', 'sivarcart-core-lang']);

        $this->publishes([
            __DIR__.'/../public' => public_path('vendor/sivarcart-core'),
        ], ['sivarcart-core', 'sivarcart-core-assets']);

        $this->publishes([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], ['sivarcart-core', 'sivarcart-core-migrations']);

        $this->commands([
            SivarcartCoreInstallCommand::class,
        ]);
    }
}
