<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Tests;

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Livewire\LivewireServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use Rmirandasv\Wompi\WompiServiceProvider;
use SivarcartCore\SivarcartCore\SivarcartCoreServiceProvider;
use Spatie\MediaLibrary\MediaLibraryServiceProvider;
use Workbench\App\Models\User;

abstract class TestCase extends Orchestra
{
    /**
     * The temporary directory the published resources are written to.
     */
    protected string $temporaryDatabasePath;

    /**
     * The temporary base path of the application.
     */
    protected string $temporaryBasePath;

    /**
     * Prepare the test case.
     */
    protected function setUp(): void
    {
        $this->temporaryDatabasePath = sys_get_temp_dir()
            .DIRECTORY_SEPARATOR.'sivarcart-core-tests'
            .DIRECTORY_SEPARATOR.Str::uuid()->toString();

        $this->temporaryBasePath = sys_get_temp_dir()
            .DIRECTORY_SEPARATOR.'sivarcart-core-tests'
            .DIRECTORY_SEPARATOR.Str::uuid()->toString();

        parent::setUp();
    }

    /**
     * @param  Application  $app
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            // The media library of Spatie brings its own configuration and its
            // own observer, so the provider has to be loaded before the
            // package sets the model of the media.
            MediaLibraryServiceProvider::class,
            LivewireServiceProvider::class,
            SivarcartCoreServiceProvider::class,
            WompiServiceProvider::class,
        ];
    }

    /**
     * Point the application to a temporary database path and to a temporary
     * base path.
     *
     * The provider publishes the migrations into the database path of the
     * application, so every test needs a directory of its own. Sharing one
     * would let the tests of a parallel run delete the files of each other.
     *
     * The base path moves too, because the provider publishes the views and
     * the translations into it: the tests of the package never write inside
     * the skeleton of Testbench.
     *
     * The tests also run against the in-memory database of Testbench, so they
     * never depend on the state of the SQLite file used by the workbench.
     *
     * @param  Application  $app
     */
    protected function getEnvironmentSetUp($app): void
    {
        $app->useDatabasePath($this->temporaryDatabasePath);

        $this->prepareTemporaryBasePath();

        $app->setBasePath($this->temporaryBasePath);

        /*
        | The views of the application come from the temporary base path too,
        | so the views the merchant publishes in
        | "resources/views/vendor/sivarcart-core" are found the same way they
        | are found in a real application.
        */
        $app['config']->set('view.paths', [
            $this->temporaryBasePath.DIRECTORY_SEPARATOR.'resources'.DIRECTORY_SEPARATOR.'views',
        ]);

        $app['config']->set('database.default', 'testing');

        // The notifications of the package go through the queue, so the tests
        // run them right away instead of leaving them in a table that does not
        // exist. The mail of the buyer is really sent, the same way it leaves
        // the store.
        $app['config']->set('queue.default', 'sync');

        // The limiter of the store API counts the requests of the buyers, so
        // the tests keep the count in memory instead of in a table that does
        // not exist.
        $app['config']->set('cache.default', 'array');

        // The package does not own the users: the staff of the store are the
        // users of the host application. The tests play the role of that
        // application, so they point the "users" provider at the model of the
        // workbench, the same way a real application does.
        $app['config']->set('auth.providers.users.model', User::class);
    }

    /**
     * Declare the routes the application declares for the account of the buyer.
     *
     * The links of the verification email and of the password reset point at
     * the pages of the application, and the package does not render them, so
     * the tests declare them the same way an application does.
     */
    protected function defineRoutes($router): void
    {
        $router->get('password/reset/{token}/{email}', fn (): string => 'ok')->name('password.reset');

        $router->get('email/verify/{id}/{hash}', fn (): string => 'ok')->name('verification.verify');
    }

    /**
     * Build a base path that looks like a Laravel application.
     *
     * Blade reads the composer.json of the base path to guess the namespace
     * of a component, so the directory needs one.
     *
     * The directory of the views the merchant publishes is created too, and it
     * is created before the application starts: Laravel looks for it when it
     * registers the views of the package, so a file that appears later would
     * not be found.
     */
    private function prepareTemporaryBasePath(): void
    {
        File::ensureDirectoryExists($this->temporaryBasePath.DIRECTORY_SEPARATOR.'app');

        File::ensureDirectoryExists(
            $this->temporaryBasePath
            .DIRECTORY_SEPARATOR.'resources'
            .DIRECTORY_SEPARATOR.'views'
            .DIRECTORY_SEPARATOR.'vendor'
            .DIRECTORY_SEPARATOR.'sivarcart-core',
        );

        File::put(
            $this->temporaryBasePath.DIRECTORY_SEPARATOR.'composer.json',
            (string) json_encode([
                'autoload' => [
                    'psr-4' => [
                        'App\\' => 'app/',
                    ],
                ],
            ], JSON_PRETTY_PRINT),
        );
    }

    /**
     * Load the migrations of the package into the test database.
     *
     * The provider only publishes them, so the test suite loads them the same
     * way a host application would run them after publishing.
     *
     * The tests play the role of the host application, so they load the
     * migration of its table of users first: the staff of the store are those
     * users, and the package relates them to them.
     */
    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/database/migrations');

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }

    /**
     * Clean up after the test case.
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        File::deleteDirectory($this->temporaryDatabasePath);
        File::deleteDirectory($this->temporaryBasePath);
    }
}
