<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Admin;

/**
 * The vocabulary of the permissions of the store.
 *
 * The roles of the store carry the permissions, and the middleware of the
 * permissions and the routes of the panel check them. The list lives here so
 * that the seeder of the roles and the panel itself speak the same words.
 */
final class AdminPermissions
{
    /**
     * See the settings of the store.
     */
    public const string SETTINGS_VIEW = 'settings.view';

    /**
     * Write the settings of the store.
     */
    public const string SETTINGS_UPDATE = 'settings.update';

    /**
     * See the catalog.
     */
    public const string CATALOG_VIEW = 'catalog.view';

    /**
     * See the orders.
     */
    public const string ORDERS_VIEW = 'orders.view';

    /**
     * Move the orders through their lifecycle.
     */
    public const string ORDERS_UPDATE = 'orders.update';

    /**
     * See the buyers of the store.
     */
    public const string CUSTOMERS_VIEW = 'customers.view';

    /**
     * See the roles of the staff.
     */
    public const string ROLES_VIEW = 'roles.view';

    /**
     * Every permission of the store.
     *
     * @return list<string>
     */
    public static function all(): array
    {
        return [
            self::SETTINGS_VIEW,
            self::SETTINGS_UPDATE,
            self::CATALOG_VIEW,
            self::ORDERS_VIEW,
            self::ORDERS_UPDATE,
            self::CUSTOMERS_VIEW,
            self::ROLES_VIEW,
        ];
    }
}
