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
    | Cart
    |--------------------------------------------------------------------------
    |
    | How long a cart lives. A cart that ran out of time is not handed back to
    | the buyer: a new one is created, and the old one is removed by the
    | "sivarcart:prune-carts" command.
    |
    */

    'cart' => [
        'ttl_days' => 30,
    ],

    /*
    |--------------------------------------------------------------------------
    | Media
    |--------------------------------------------------------------------------
    |
    | The images of the products, of the brands and of the store. The files
    | live on the disk of the "disk" key and the thumbnails are the ones of
    | the "thumbnails" map: each one says how wide and how tall the derived
    | image is.
    |
    | The package stores the media with Spatie Media Library, so the store
    | needs the "exif" extension of PHP and, for the thumbnails, the "gd" one.
    |
    */

    'media' => [
        'disk' => 'public',

        /*
        | How big a file may be, in kilobytes.
        */
        'max_size' => 10240,

        /*
        | The kinds of file the store accepts.
        */
        'mimes' => ['jpg', 'jpeg', 'png', 'webp'],

        /*
        | Whether the thumbnails are built in the background. When it is false
        | they are built while the file is being uploaded, so the store does
        | not need a worker of the queue.
        */
        'queue_conversions' => false,

        'thumbnails' => [
            'thumb' => ['width' => 300, 'height' => 300],
            'medium' => ['width' => 800, 'height' => 800],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | API
    |--------------------------------------------------------------------------
    |
    | The store API of the package. The prefix is configurable, so the
    | application can mount the API wherever it wants, and the middleware
    | group is the one of the application: "api" by default, which already
    | comes with the bindings of the routes.
    |
    | The rate limit counts the requests of every buyer on its own, and the
    | requests of the buyers that are not signed in by IP.
    |
    */

    'api' => [
        'enabled' => true,
        'prefix' => 'api/sivarcart',
        'middleware' => ['api'],
        'rate_limit' => 60,
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

    /*
    |--------------------------------------------------------------------------
    | Administration panel
    |--------------------------------------------------------------------------
    |
    | The panel of the staff of the store lives in the package. It is a set of
    | Livewire components and Blade views that the merchant overrides in
    | "resources/views/vendor/sivarcart-core", the same way every package of
    | Laravel does it.
    |
    | The panel does not authenticate the staff: it uses the users, the guard
    | and the session of the host application.
    |
    */

    'admin' => [
        'enabled' => true,

        /*
        | Where the panel is mounted. Its routes are registered by the package,
        | so the application does not have to publish them.
        */
        'prefix' => 'sivarcart/admin',

        /*
        | The middleware of the panel. The "web" group brings the session and
        | the CSRF. The package adds its own middleware, which sends the staff
        | to the sign in screen of the panel and checks that the model of the
        | user carries the trait of the roles of the store.
        */
        'middleware' => ['web'],

        /*
        | The guard that holds the session of the staff. When it is null the
        | default guard of "config/auth.php" is used.
        */
        'guard' => null,

        'login' => [
            /*
            | Whether the package renders the sign in screen of the panel. When
            | it is false the application sends the staff to its own sign in.
            */
            'enabled' => true,

            /*
            | The route of the application that signs the staff in. When it is
            | null the package uses the one of the panel.
            */
            'route' => null,
        ],

        /*
        | The theme of the panel. These values become CSS custom properties,
        | so the merchant changes them without compiling anything.
        */
        'theme' => [
            'primary' => '#1d4ed8',
            'primary_hover' => '#1e40af',
            'danger' => '#b91c1c',
            'success' => '#15803d',
            'warning' => '#b45309',
            'radius' => '0.5rem',
            'font' => 'system-ui, -apple-system, "Segoe UI", sans-serif',
        ],

        /*
        | Extra stylesheets of the application. They load after the stylesheet
        | of the panel, so they win.
        */
        'stylesheets' => [],

        'per_page' => 25,
    ],

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
