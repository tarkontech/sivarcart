<?php

declare(strict_types=1);

namespace Tarkontech\SivarCart\Support;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;

/**
 * The pages the account of the buyer points at.
 *
 * The store draws the screen that changes the password, so the link of the
 * email points at the store when the store is on. The application that wants
 * its own screen names it in the configuration of the package, and the
 * package points at it.
 *
 * The verification of the email is another matter: the store does not draw
 * it, así que la pantalla es siempre de la aplicación.
 */
final class BuyerRoutes
{
    /**
     * The screen of la tienda que cambia la contraseña.
     */
    public const string STOREFRONT_PASSWORD_RESET = 'sivarcart.storefront.account.password.reset';

    /**
     * The screen of la aplicación que cambia la contraseña.
     */
    public const string APPLICATION_PASSWORD_RESET = 'password.reset';

    /**
     * The screen where the buyer changes the password.
     */
    public static function passwordReset(): string
    {
        $route = Config::get('sivarcart.buyers.password_reset_route');

        if (is_string($route) && trim($route) !== '') {
            return trim($route);
        }

        if (self::storefrontIsOn() && Route::has(self::STOREFRONT_PASSWORD_RESET)) {
            return self::STOREFRONT_PASSWORD_RESET;
        }

        return self::APPLICATION_PASSWORD_RESET;
    }

    /**
     * Whether the store of the shopper is turned on.
     */
    private static function storefrontIsOn(): bool
    {
        return (bool) Config::get('sivarcart.storefront.enabled', true);
    }
}
