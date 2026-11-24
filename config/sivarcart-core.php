<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Store defaults
    |--------------------------------------------------------------------------
    |
    | The values the store is created with the first time the package is
    | installed. They can be changed later from the administration panel.
    |
    */

    'store' => [
        'name' => 'SivarCart',
        'slug' => 'sivarcart',
        'legal_name' => null,
        'tax_id' => null,
        'email' => null,
        'phone' => null,
        'currency' => 'USD',
        'timezone' => 'America/El_Salvador',
        'locale' => 'es_SV',
        'prices_include_tax' => true,
        'default_tax_rate' => 0.13,
        'low_stock_threshold' => 5,
        'order_number_prefix' => 'SIV',
        'payment_number_prefix' => 'PAY',
    ],

    /*
    |--------------------------------------------------------------------------
    | Authentication
    |--------------------------------------------------------------------------
    |
    | The package does not authenticate the staff of the store. The
    | credentials, the sessions and the password resets belong to the
    | application that installs it, so the staff of the store are the users of
    | the application.
    |
    | These two keys only tell the package which model holds those users and
    | which guard holds their session. When they are null the package reads
    | the "users" provider and the default guard of "config/auth.php", which
    | is what an application that has not changed them needs.
    |
    | The buyers are a different matter: they live in this package, so it
    | brings its own guard, its own provider and its own password broker. See
    | the "buyers" section below.
    |
    */

    'auth' => [
        'model' => null,
        'guard' => null,
    ],

    /*
    |--------------------------------------------------------------------------
    | Buyers
    |--------------------------------------------------------------------------
    |
    | The buyer of the store is a customer of the package: it signs in with
    | the guard of this section, and it cannot place an order without an
    | account.
    |
    | The package registers the guard and its provider in the configuration of
    | the application when it boots, so the application does not have to touch
    | its "config/auth.php". The names of the guard and of the provider are
    | read from here, so a merchant that already uses those names can change
    | them.
    |
    | The two routes are the ones the application declares for its own pages:
    | the links of the verification email and of the password reset point at
    | them. The package does not render those pages.
    |
    */

    'buyers' => [
        'guard' => 'sivarcart',
        'provider' => 'sivarcart',
        'password_reset_table' => 'sivarcart_password_reset_tokens',
        'password_expire_minutes' => 60,
        'password_reset_route' => 'password.reset',
        'email_verification_route' => 'verification.verify',
    ],

    /*
    |--------------------------------------------------------------------------
    | Orders
    |--------------------------------------------------------------------------
    |
    | The document numbers are consecutive per year: SIV-2026-00001.
    |
    */

    'orders' => [
        'number_padding' => 5,
        'reference_length' => 128,
    ],

    /*
    |--------------------------------------------------------------------------
    | Catalog
    |--------------------------------------------------------------------------
    */

    'catalog' => [
        'low_stock_threshold' => 5,
    ],

    /*
    |--------------------------------------------------------------------------
    | Notifications
    |--------------------------------------------------------------------------
    |
    | The package tells the buyer when the order moves. Which of those emails
    | leave the store is a decision of the merchant, so it lives in the
    | database: the table of the notification settings holds one row per
    | notification the admin has turned on or off.
    |
    | The "enabled" key is the value used for the notifications that have no
    | row saved yet, so a store that just installed the package already tells
    | the buyer what is going on with the order.
    |
    */

    'notifications' => [
        'enabled' => true,

        /*
        | The address the emails come from. When it is null the address of the
        | "mail.from" configuration of the application is used.
        */
        'from' => null,
    ],

    /*
    |--------------------------------------------------------------------------
    | Wompi
    |--------------------------------------------------------------------------
    |
    | The credentials themselves are stored per payment method, so a merchant
    | can configure them from the administration panel. They fall back to the
    | WOMPI_CLIENT_ID and WOMPI_CLIENT_SECRET environment variables of the
    | laravel-wompi package when the payment method has none.
    |
    */

    'wompi' => [
        'enabled' => true,

        /*
        | The payment gateway used by the payment methods that do not name one.
        | A module can register another driver on the payment gateway manager.
        */
        'driver' => 'wompi',

        'webhook_path' => 'webhooks/wompi',

        /*
        | Where Wompi sends the shopper back once the payment is done. When it
        | is null the URL is built with the URL of the application and the
        | redirect path.
        */
        'redirect_path' => 'payments/wompi/return',
        'redirect_url' => null,

        'payment_link' => [
            'permitir_tarjeta_credito_debito' => true,
            'permitir_pago_con_punto_agricola' => true,
            'permitir_pago_en_cuotas_agricola' => false,
        ],
    ],

];
