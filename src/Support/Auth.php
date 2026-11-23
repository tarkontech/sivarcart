<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Support;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth as AuthFacade;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

/**
 * Resolves the user of the host application.
 *
 * The package never authenticates anybody: the credentials, the sessions and
 * the password resets belong to the application that installs it. This class
 * only answers the two questions the package asks about that application:
 * which model holds the users and which guard holds their session.
 */
final class Auth
{
    /**
     * The model that holds the users of the host application.
     *
     * The model comes from the "auth" section of the configuration of the
     * package when the application names one, and from the "users" provider
     * of "config/auth.php" otherwise.
     *
     * @return class-string<Model>
     */
    public static function model(): string
    {
        $model = Config::get('sivarcart-core.auth.model') ?? Config::get('auth.providers.users.model');

        if (! is_string($model) || $model === '' || ! is_a($model, Model::class, true)) {
            throw new RuntimeException(
                'SivarCart Core could not find the user model of the application. Set the "model" key of the "sivarcart-core.auth" configuration, or the "model" key of the "users" provider of "config/auth.php".',
            );
        }

        return $model;
    }

    /**
     * The model of the user of the host application, ready to be used.
     *
     * @return Model&Authenticatable
     */
    public static function newModel(): Model
    {
        $model = new (self::model());

        if (! $model instanceof Authenticatable) {
            throw new RuntimeException(sprintf(
                'The model [%s] of the host application is not authenticatable. SivarCart Core reads the user of the application, so the model of the "users" provider of "config/auth.php" has to extend [%s].',
                $model::class,
                Authenticatable::class,
            ));
        }

        return $model;
    }

    /**
     * The guard that holds the session of the users.
     *
     * The guard comes from the "auth" section of the configuration of the
     * package when the application names one, and from the default guard of
     * "config/auth.php" otherwise.
     */
    public static function guard(): string
    {
        $guard = Config::get('sivarcart-core.auth.guard') ?? Config::get('auth.defaults.guard');

        if (! is_string($guard) || $guard === '') {
            return 'web';
        }

        return $guard;
    }

    /**
     * The user that is authenticated right now, if there is one.
     */
    public static function user(): ?Authenticatable
    {
        return AuthFacade::guard(self::guard())->user();
    }

    /**
     * Whether the user holds a permission of the store.
     *
     * The trait of the roles of the store is optional, so a user whose model
     * does not carry it holds no permission at all.
     */
    public static function hasStorePermission(?Authenticatable $user, string $permission): bool
    {
        if ($user === null) {
            return false;
        }

        if (! method_exists($user, 'hasStorePermission')) {
            return false;
        }

        return $user->hasStorePermission($permission);
    }

    /**
     * Make sure the table of the users of the host application exists.
     *
     * The migrations of the package derive the foreign key of the staff from
     * the model of the user, so the application has to create its own table
     * before they run.
     */
    public static function assertUserTableExists(): void
    {
        $table = self::newModel()->getTable();

        if (! Schema::hasTable($table)) {
            throw new RuntimeException(sprintf(
                'The table [%s] of the host application does not exist yet. SivarCart Core relates the staff of the store to the users of the application, so the migrations of the application have to run before the ones of the package.',
                $table,
            ));
        }
    }
}
