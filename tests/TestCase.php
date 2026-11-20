<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Tests;

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Orchestra\Testbench\TestCase as Orchestra;
use Rmirandasv\Wompi\WompiServiceProvider;
use SivarcartCore\SivarcartCore\SivarcartCoreServiceProvider;
use Workbench\App\Models\User;

abstract class TestCase extends Orchestra
{
    /**
     * The temporary directory the published resources are written to.
     */
    protected string $temporaryDatabasePath;

    /**
     * Prepare the test case.
     */
    protected function setUp(): void
    {
        $this->temporaryDatabasePath = sys_get_temp_dir()
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
            SivarcartCoreServiceProvider::class,
            WompiServiceProvider::class,
        ];
    }

    /**
     * Point the application to a temporary database path.
     *
     * The provider publishes the migrations into the database path of the
     * application, so every test needs a directory of its own. Sharing one
     * would let the tests of a parallel run delete the files of each other.
     *
     * The tests also run against the in-memory database of Testbench, so they
     * never depend on the state of the SQLite file used by the workbench.
     *
     * @param  Application  $app
     */
    protected function getEnvironmentSetUp($app): void
    {
        $app->useDatabasePath($this->temporaryDatabasePath);

        $app['config']->set('database.default', 'testing');

        // The package does not own the users: the staff of the store are the
        // users of the host application. The tests play the role of that
        // application, so they point the "users" provider at the model of the
        // workbench, the same way a real application does.
        $app['config']->set('auth.providers.users.model', User::class);
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
    }
}
