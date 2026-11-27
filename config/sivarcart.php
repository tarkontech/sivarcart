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
    | The route of la pantalla que cambia la clave es la de la tienda cuando
    | la tienda está encendida: la tienda dibuja esa pantalla. Cuando la
    | tienda está apagada, la aplicación declara la suya y la nombra aquí.
    |
    | La verificación de correo es otra cosa: la tienda no la dibuja, así que
    | la aplicación la declara y la nombra aquí.
    |
    */

    'buyers' => [
        'guard' => 'sivarcart',
        'provider' => 'sivarcart',
        'password_reset_table' => 'sivarcart_password_reset_tokens',
        'password_expire_minutes' => 60,
        'password_reset_route' => null,
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
    | The panel of the staff of the store lives in the package. It is an
    | Inertia application whose screens are React components written in
    | TypeScript, and it brings its own bundle already compiled, so the
    | merchant installs the package and the panel is there.
    |
    | The merchant that wants its own interface publishes the source of the
    | panel, compiles it and its bundle wins over the one of the package.
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
        | The theme of the panel. These values become CSS custom properties of
        | the design system of the panel, so the merchant changes them without
        | compiling anything.
        |
        | The names are the ones of the tokens: "primary" is the color of the
        | main action, "destructive" the one of the danger, "success" and
        | "warning" the ones of the states of the store. The surfaces —the
        | background, the cards, the muted text— belong to the stylesheet of
        | the package, because they are the ones that keep the panel readable
        | in the light theme and in the dark one.
        */
        'theme' => [
            'primary' => '#1d4ed8',
            'primary_foreground' => '#ffffff',
            'destructive' => '#b91c1c',
            'destructive_foreground' => '#ffffff',
            'success' => '#15803d',
            'success_foreground' => '#ffffff',
            'warning' => '#b45309',
            'warning_foreground' => '#ffffff',
            'radius' => '0.5rem',
            'font' => 'system-ui, -apple-system, "Segoe UI", sans-serif',
        ],

        /*
        | The theme of the panel for the dark mode. These values are written
        | after the ones of the light theme, so they win on it. When they are
        | empty the panel uses the brand of the light theme in the dark one.
        */
        'theme_dark' => [],

        /*
        | The bundle of the panel.
        |
        | The package brings its own bundle compiled and serves it, so the
        | merchant installs the package and the panel is there, without Node
        | and without a build step. A merchant that wants its own interface
        | publishes the source of the panel, writes its own entry and compiles
        | it: the package looks at the manifest of the application and, when
        | the entry is in it, loads that bundle instead of its own.
        */
        'bundle' => [
            'entry' => 'resources/js/sivarcart/admin.tsx',
            'directory' => 'build',
        ],

        /*
        | Extra stylesheets of the application. They load after the stylesheet
        | of the panel, so they win.
        */
        'stylesheets' => [],

        'per_page' => 25,
    ],

    /*
    |--------------------------------------------------------------------------
    | Tienda del comprador
    |--------------------------------------------------------------------------
    |
    | La tienda es la pantalla donde el cliente compra. Es una aplicación de
    | Inertia y React que vive en el paquete, con su propio bundle ya
    | compilado, así que el comerciante instala el paquete y la tienda está.
    |
    | La tienda y la API son dos maneras de montar la misma tienda, y
    | conviven: la API sigue siendo la vía del modo headless y la de los
    | clientes externos (apps móviles, integraciones). Cada una se apaga
    | sola, así que un comerciante que quiere su propia interfaz apaga la
    | tienda y deja la API.
    |
    | Los segmentos de las direcciones son los que el comprador ve, así que
    | están en español; los nombres de las rutas son del paquete y están en
    | inglés.
    |
    */

    'storefront' => [
        'enabled' => true,

        /*
        | Dónde se monta la tienda. Sus rutas las registra el paquete, así que
        | la aplicación no tiene que publicarlas.
        */
        'prefix' => 'tienda',

        /*
        | El middleware de la tienda. El grupo "web" trae la sesión y el
        | CSRF: la tienda escribe (el carrito, el checkout y la cuenta), así
        | que necesita los dos.
        */
        'middleware' => ['web'],

        /*
        | El tema de la tienda. Estos valores son las propiedades CSS del
        | sistema de diseño de la tienda, así que el comerciante los cambia
        | sin compilar nada.
        |
        | La tienda tiene su propio tema y no el del panel: son dos caras
        | distintas de la misma tienda, y cada una lleva su marca.
        */
        'theme' => [
            'primary' => '#1d4ed8',
            'primary_foreground' => '#ffffff',
            'destructive' => '#b91c1c',
            'destructive_foreground' => '#ffffff',
            'success' => '#15803d',
            'success_foreground' => '#ffffff',
            'warning' => '#b45309',
            'warning_foreground' => '#ffffff',
            'radius' => '0.5rem',
            'font' => 'system-ui, -apple-system, "Segoe UI", sans-serif',
        ],

        /*
        | El tema de la tienda para el modo oscuro. Estos valores se escriben
        | después de los del tema claro, así que ganan sobre ellos.
        */
        'theme_dark' => [],

        /*
        | El bundle de la tienda.
        |
        | El paquete trae su propio bundle compilado y lo sirve, así que el
        | comerciante instala el paquete y la tienda está, sin Node y sin un
        | paso de compilación. El comerciante que quiere su propia interfaz
        | publica el fuente de la tienda y compila el suyo: el paquete mira
        | el manifiesto de la aplicación y, cuando la entrada del
        | comerciante está en él, carga ese bundle en lugar del suyo.
        */
        'bundle' => [
            'entry' => 'resources/js/sivarcart/storefront.tsx',
            'directory' => 'build',
        ],

        /*
        | Extra stylesheets de la aplicación. Cargan después de la hoja de
        | estilo de la tienda, así que ganan.
        */
        'stylesheets' => [],

        'per_page' => 15,
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
