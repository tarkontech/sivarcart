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
    | The package does not authenticate anybody. The credentials, the sessions
    | and the password resets belong to the application that installs it, so
    | the staff of the store are the users of the application.
    |
    | These two keys only tell the package which model holds those users and
    | which guard holds their session. When they are null the package reads
    | the "users" provider and the default guard of "config/auth.php", which
    | is what an application that has not changed them needs.
    |
    */

    'auth' => [
        'model' => null,
        'guard' => null,
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
