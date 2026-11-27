<?php

declare(strict_types=1);

namespace Tarkontech\SivarCart\Admin;

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
     * Write the catalog.
     *
     * The products, their variants, the categories, the brands, the shipping
     * methods and the payment methods. Reading the catalog and writing it are
     * two different permissions, so the staff that only look at the catalog
     * cannot change it.
     */
    public const string CATALOG_UPDATE = 'catalog.update';

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
     * Write the roles of the staff.
     *
     * The permissions a role grants are changed here, so the role that grants
     * a permission is not the same one that hands it out.
     */
    public const string ROLES_UPDATE = 'roles.update';

    /**
     * See the staff of the store.
     */
    public const string STAFF_VIEW = 'staff.view';

    /**
     * Write the staff of the store.
     *
     * Giving a member of the staff a role and taking it away. The panel does
     * not delete the users of the application: it takes the roles of the
     * store away from them, so they stop seeing the panel.
     */
    public const string STAFF_UPDATE = 'staff.update';

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
            self::CATALOG_UPDATE,
            self::ORDERS_VIEW,
            self::ORDERS_UPDATE,
            self::CUSTOMERS_VIEW,
            self::ROLES_VIEW,
            self::ROLES_UPDATE,
            self::STAFF_VIEW,
            self::STAFF_UPDATE,
        ];
    }
}
