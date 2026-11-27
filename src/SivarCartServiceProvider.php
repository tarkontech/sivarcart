<?php

declare(strict_types=1);

namespace Tarkontech\SivarCart;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\ServiceProvider;
use Tarkontech\SivarCart\Admin\AdminPanel;
use Tarkontech\SivarCart\Admin\Pages\BrandsIndexProps;
use Tarkontech\SivarCart\Admin\Pages\CategoriesIndexProps;
use Tarkontech\SivarCart\Admin\Pages\CustomersIndexProps;
use Tarkontech\SivarCart\Admin\Pages\DashboardProps;
use Tarkontech\SivarCart\Admin\Pages\OrdersIndexProps;
use Tarkontech\SivarCart\Admin\Pages\PaymentMethodsIndexProps;
use Tarkontech\SivarCart\Admin\Pages\ProductsIndexProps;
use Tarkontech\SivarCart\Admin\Pages\RolesIndexProps;
use Tarkontech\SivarCart\Admin\Pages\SettingsIndexProps;
use Tarkontech\SivarCart\Admin\Pages\ShippingMethodsIndexProps;
use Tarkontech\SivarCart\Admin\Pages\StaffIndexProps;
use Tarkontech\SivarCart\Console\Commands\CreateAdminCommand;
use Tarkontech\SivarCart\Console\Commands\PruneCartsCommand;
use Tarkontech\SivarCart\Console\Commands\RestockCommand;
use Tarkontech\SivarCart\Console\Commands\SivarCartInstallCommand;
use Tarkontech\SivarCart\Console\Commands\SivarCartPublishMailablesCommand;
use Tarkontech\SivarCart\Contracts\PaymentGateway;
use Tarkontech\SivarCart\Contracts\ShippingRateCalculator;
use Tarkontech\SivarCart\Events\OrderCancelled;
use Tarkontech\SivarCart\Events\OrderDelivered;
use Tarkontech\SivarCart\Events\OrderPaid;
use Tarkontech\SivarCart\Events\OrderPlaced;
use Tarkontech\SivarCart\Events\OrderRefunded;
use Tarkontech\SivarCart\Events\OrderShipped;
use Tarkontech\SivarCart\Events\PaymentFailed;
use Tarkontech\SivarCart\Http\ApiExceptionRenderer;
use Tarkontech\SivarCart\Http\Middleware\EnsureBuyer;
use Tarkontech\SivarCart\Http\Middleware\EnsureStoreAdmin;
use Tarkontech\SivarCart\Http\Middleware\EnsureStorefrontBuyer;
use Tarkontech\SivarCart\Http\Middleware\EnsureStorePermission;
use Tarkontech\SivarCart\Http\Middleware\StorefrontApi;
use Tarkontech\SivarCart\Listeners\SendOrderCancelledNotification;
use Tarkontech\SivarCart\Listeners\SendOrderDeliveredNotification;
use Tarkontech\SivarCart\Listeners\SendOrderPaidNotification;
use Tarkontech\SivarCart\Listeners\SendOrderPlacedNotification;
use Tarkontech\SivarCart\Listeners\SendOrderRefundedNotification;
use Tarkontech\SivarCart\Listeners\SendOrderShippedNotification;
use Tarkontech\SivarCart\Listeners\SendPaymentFailedNotification;
use Tarkontech\SivarCart\Models\Customer;
use Tarkontech\SivarCart\Models\ProductImage;
use Tarkontech\SivarCart\Payments\PaymentGatewayManager;
use Tarkontech\SivarCart\Services\Admin\CustomerService as AdminCustomerService;
use Tarkontech\SivarCart\Services\Admin\OrderService as AdminOrderService;
use Tarkontech\SivarCart\Services\Admin\ProductService as AdminProductService;
use Tarkontech\SivarCart\Services\Admin\StatsService;
use Tarkontech\SivarCart\Services\CartService;
use Tarkontech\SivarCart\Services\CatalogService;
use Tarkontech\SivarCart\Services\CouponService;
use Tarkontech\SivarCart\Services\CustomerService;
use Tarkontech\SivarCart\Services\DepartmentService;
use Tarkontech\SivarCart\Services\InventoryService;
use Tarkontech\SivarCart\Services\MediaService;
use Tarkontech\SivarCart\Services\NotificationService;
use Tarkontech\SivarCart\Services\OrderService;
use Tarkontech\SivarCart\Services\PaymentMethodService;
use Tarkontech\SivarCart\Services\PaymentService;
use Tarkontech\SivarCart\Services\PricingService;
use Tarkontech\SivarCart\Services\SequenceService;
use Tarkontech\SivarCart\Services\ShippingMethodRateCalculator;
use Tarkontech\SivarCart\Services\ShippingMethodService;
use Tarkontech\SivarCart\Services\StoreService;
use Tarkontech\SivarCart\Support\Auth;
use Tarkontech\SivarCart\Support\Media;

class SivarCartServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/sivarcart.php', 'sivarcart');

        $this->app->singleton(SivarCart::class);

        $this->registerBuyersAuthentication();
        $this->registerMediaLibrary();
        $this->registerServices();
        $this->registerContracts();

        $this->registerAdminPages();
    }

    /**
     * Tell the media library of Spatie how the package stores its files.
     *
     * The settings are written here and not in "boot()", because the media
     * library reads the model of the media when it boots: it has to find the
     * model of this package and not the one that comes by default.
     *
     * The package does not use the table of the media library: its own table
     * of images is the one that holds the media, so the migration of the
     * media library does not have to be published.
     */
    private function registerMediaLibrary(): void
    {
        Config::set('media-library.media_model', ProductImage::class);
        Config::set('media-library.disk_name', Media::disk());
        Config::set('media-library.max_file_size', Media::maxSize() * 1024);
        Config::set('media-library.allowed_extensions', Media::allowedMimes());
        Config::set('media-library.queue_conversions_by_default', Media::queuesConversions());
    }

    /**
     * Register the guard, the provider and the broker of the buyers.
     *
     * The package does not touch the "config/auth.php" of the application: it
     * writes its own guard and its own provider in the configuration, so the
     * application signs the buyers in without registering anything.
     *
     * The provider names the driver of the framework, the same one it builds
     * for the users of an application.
     */
    private function registerBuyersAuthentication(): void
    {
        $guard = (string) config('sivarcart.buyers.guard', 'sivarcart');
        $provider = (string) config('sivarcart.buyers.provider', 'sivarcart');

        Config::set('auth.providers.'.$provider, [
            'driver' => 'eloquent',
            'model' => Customer::class,
        ]);

        Config::set('auth.guards.'.$guard, [
            'driver' => 'session',
            'provider' => $provider,
        ]);

        Config::set('auth.passwords.'.$provider, [
            'provider' => $provider,
            'table' => (string) config(
                'sivarcart.buyers.password_reset_table',
                'sivarcart_password_reset_tokens',
            ),
            'expire' => (int) config('sivarcart.buyers.password_expire_minutes', 60),
        ]);
    }

    /**
     * Register the services of the domain.
     */
    private function registerServices(): void
    {
        foreach ([
            CatalogService::class,
            StoreService::class,
            SequenceService::class,
            PricingService::class,
            CouponService::class,
            InventoryService::class,
            CartService::class,
            CustomerService::class,
            DepartmentService::class,
            OrderService::class,
            PaymentService::class,
            PaymentMethodService::class,
            ShippingMethodService::class,
            PaymentGatewayManager::class,
            NotificationService::class,
            MediaService::class,
            StatsService::class,
            AdminProductService::class,
            AdminCustomerService::class,
            AdminOrderService::class,
        ] as $service) {
            $this->app->singleton($service);
        }

        $this->app->singleton(AdminPanel::class);
    }

    /**
     * Register the points of extension a module can replace.
     */
    private function registerContracts(): void
    {
        $this->app->bind(
            ShippingRateCalculator::class,
            ShippingMethodRateCalculator::class,
        );

        $this->app->bind(
            PaymentGateway::class,
            fn (Application $app): PaymentGateway => $app->make(PaymentGatewayManager::class)->driver(),
        );
    }

    /**
     * Bootstrap any application services.
     *
     * The migrations of this package are published, never loaded from the
     * package itself. Laravel resolves the class of a migration by stripping
     * its timestamp, so a published copy and a package copy would end up
     * declaring the same class and "php artisan migrate" would fail with
     * "Cannot declare class". Publishing keeps the merchant in charge of the
     * schema, and "vendor:publish" never overwrites a file that already
     * exists, so a customisation survives an upgrade.
     *
     * The migrations are published with "publishes" instead of
     * "publishesMigrations" on purpose: the latter dates every published
     * migration with the current date, so publishing twice would leave two
     * copies of the same migration behind and "php artisan migrate" would
     * fail with "table already exists". Keeping the original names makes a
     * second publish skip the files that are already there and only add the
     * new ones.
     */
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/sivarcart.php');

        $this->loadViewsFrom(__DIR__.'/../resources/views', 'sivarcart');

        /*
        | The root view of the panel lives in a namespace of its own, and not in
        | the namespace of the views of the package. The views of the package
        | are published and the merchant can put its own in
        | "resources/views/vendor/sivarcart", and the root view of an
        | Inertia application is not something to overwrite by accident: a
        | wrong file there breaks the mounting of the panel in silence.
        */
        $this->loadViewsFrom(__DIR__.'/../resources/views/panel', 'sivarcart-panel');

        /*
        | The root view of the store lives in a namespace of its own, for the
        | same reason that the one of the panel does.
        */
        $this->loadViewsFrom(__DIR__.'/../resources/views/storefront', 'sivarcart-storefront');

        /*
        | The pages of the panel live in the package, so Inertia has to know
        | where they are. The folder of the application is kept, so an
        | application that has an Inertia of its own keeps its own pages.
        |
        | The pages of the store live in their own folder, so the two
        | applications of the package never take a page of the other.
        */
        Config::set('inertia.pages.paths', [
            ...(array) Config::get('inertia.pages.paths', []),
            __DIR__.'/../resources/js/admin/pages',
            __DIR__.'/../resources/js/storefront/pages',
        ]);

        $this->loadTranslationsFrom(__DIR__.'/../lang', 'sivarcart');

        $this->registerNotificationListeners();

        $this->registerStorePermissions();

        $this->registerStoreApi();

        $this->registerAdminPanel();

        $this->registerStorefront();

        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->publishes([
            __DIR__.'/../config/sivarcart.php' => config_path('sivarcart.php'),
        ], ['sivarcart', 'sivarcart-config']);

        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/sivarcart'),
        ], ['sivarcart', 'sivarcart-views']);

        /*
        | The root view of the panel is published on its own tag, and not with
        | the views of the package: it is the view that mounts the panel, so
        | the merchant that wants to change the head or the logo of the panel
        | asks for it explicitly.
        */
        $this->publishes([
            __DIR__.'/../resources/views/panel' => resource_path('views/vendor/sivarcart-panel'),
        ], ['sivarcart', 'sivarcart-panel-view']);

        /*
        | The source of the panel is published for the merchant that wants to
        | write its own interface. The published files are never overwritten,
        | so a customization survives an update of the package.
        */
        $this->publishes([
            __DIR__.'/../resources/js/admin' => resource_path('js/sivarcart'),
        ], ['sivarcart', 'sivarcart-admin-source']);

        /*
        | The source of the store is published for the merchant that wants to
        | write its own storefront. It lives in its own folder, so the two
        | interfaces of the package never share a file.
        */
        $this->publishes([
            __DIR__.'/../resources/js/storefront' => resource_path('js/sivarcart/storefront'),
        ], ['sivarcart', 'sivarcart-storefront-source']);
        /*
        | The root view of the store is published on its own tag, and not with
        | las vistas del paquete: es la vista que monta la tienda, así que el
        | comerciante que quiere cambiar el "head" o los datos de la página
        | lo pide de forma explícita.
        */
        $this->publishes([
            __DIR__.'/../resources/views/storefront' => resource_path('views/vendor/sivarcart-storefront'),
        ], ['sivarcart', 'sivarcart-storefront-view']);

        $this->publishes([
            __DIR__.'/../resources/views/mail' => resource_path('views/vendor/sivarcart/mail'),
        ], ['sivarcart', 'sivarcart-mailables']);

        $this->publishes([
            __DIR__.'/../lang' => $this->app->langPath('vendor/sivarcart'),
        ], ['sivarcart', 'sivarcart-lang']);

        $this->publishes([
            __DIR__.'/../public' => public_path('vendor/sivarcart'),
        ], ['sivarcart', 'sivarcart-assets']);

        $this->publishes([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], ['sivarcart', 'sivarcart-migrations']);

        $this->commands([
            SivarCartInstallCommand::class,
            SivarCartPublishMailablesCommand::class,
            CreateAdminCommand::class,
            PruneCartsCommand::class,
            RestockCommand::class,
        ]);

        $this->schedulePruneCarts();
    }

    /**
     * Remove the carts that expired, once a day.
     *
     * The task is scheduled by the package so the application does not have to
     * schedule it. It is registered in the console only, so it never touches a
     * web request.
     */
    private function schedulePruneCarts(): void
    {
        Schedule::command('sivarcart:prune-carts')->daily();
    }

    /**
     * Write to the buyer when the order moves.
     *
     * The listeners are registered here and not by discovery, so the wiring of
     * the package is written down in one place.
     */
    private function registerNotificationListeners(): void
    {
        Event::listen(OrderPlaced::class, SendOrderPlacedNotification::class);
        Event::listen(OrderPaid::class, SendOrderPaidNotification::class);
        Event::listen(OrderShipped::class, SendOrderShippedNotification::class);
        Event::listen(OrderDelivered::class, SendOrderDeliveredNotification::class);
        Event::listen(OrderCancelled::class, SendOrderCancelledNotification::class);
        Event::listen(OrderRefunded::class, SendOrderRefundedNotification::class);
        Event::listen(PaymentFailed::class, SendPaymentFailedNotification::class);
    }

    /**
     * Let the application protect its routes with the permissions of the store.
     *
     * The middleware is registered with an alias so the routes of the
     * application can use it by name, and the ability of the Gate lets the
     * templates of the application ask for a permission with @can.
     *
     * The ability is only defined when the application has not defined it
     * already: the providers of the package boot after the ones of the
     * application, so an unconditional definition would take the decision of
     * the application away.
     */
    private function registerStorePermissions(): void
    {
        Route::aliasMiddleware('store.permission', EnsureStorePermission::class);

        if (! Gate::has('store-permission')) {
            Gate::define('store-permission', function (Authenticatable $user, string $permission): bool {
                return Auth::hasStorePermission($user, $permission);
            });
        }
    }

    /**
     * Register the store API of the package.
     *
     * The routes are registered by the package, so the application does not
     * have to publish them. The middleware of the API is registered with an
     * alias, and the rate limiter is registered with the name "sivarcart"
     * (the framework does not define an "api" limiter).
     */
    private function registerStoreApi(): void
    {
        Route::aliasMiddleware('sivarcart.api', StorefrontApi::class);
        Route::aliasMiddleware('sivarcart.buyer', EnsureBuyer::class);

        $this->registerApiRateLimiter();

        $this->registerApiExceptionRenderer();

        if (! (bool) config('sivarcart.api.enabled', true)) {
            return;
        }

        $this->loadRoutesFrom(__DIR__.'/../routes/api.php');
    }

    /**
     * Register the administration panel of the staff of the store.
     *
     * The panel is an Inertia application: its pages are components of React
     * and the bundle of the panel travels with the package. The merchant
     * overrides them by publishing the source of the panel and compiling its
     * own bundle, or by putting its own page in the registry.
     *
     * The panel has its own root view and its own middleware of Inertia, in a
     * namespace of its own, so an application that already has an Inertia of
     * its own keeps it untouched.
     *
     * The panel does not authenticate the staff: it uses the users, the guard
     * and the session of the host application.
     */
    private function registerAdminPanel(): void
    {
        if (! (bool) config('sivarcart.admin.enabled', true)) {
            return;
        }

        Route::aliasMiddleware('sivarcart.admin', EnsureStoreAdmin::class);

        $this->loadRoutesFrom(__DIR__.'/../routes/admin.php');
    }

    /**
     * Register the pages the panel brings.
     *
     * The pages are registered in "register()" and not in "boot()", so the
     * application can replace one of them by registering a page with the same
     * key.
     *
     * The label of a page is the key of its translation, so the panel speaks
     * the language of the application. The label of a page the host registers
     * is the text the host wants.
     */
    private function registerAdminPages(): void
    {
        if (! (bool) config('sivarcart.admin.enabled', true)) {
            return;
        }

        $panel = $this->app->make(AdminPanel::class);

        $panel->page(
            key: 'dashboard',
            label: 'sivarcart::admin.dashboard.title',
            component: 'Admin/Dashboard',
            props: new DashboardProps,
            order: 10,
        );

        $panel->page(
            key: 'orders',
            label: 'sivarcart::admin.orders.title',
            component: 'Admin/Orders/Index',
            props: new OrdersIndexProps,
            permission: 'orders.view',
            order: 20,
        );

        $panel->page(
            key: 'products',
            label: 'sivarcart::admin.products.title',
            component: 'Admin/Products/Index',
            props: new ProductsIndexProps,
            permission: 'catalog.view',
            order: 30,
        );

        $panel->page(
            key: 'categories',
            label: 'sivarcart::admin.categories.title',
            component: 'Admin/Categories/Index',
            props: new CategoriesIndexProps,
            permission: 'catalog.view',
            order: 31,
        );

        $panel->page(
            key: 'brands',
            label: 'sivarcart::admin.brands.title',
            component: 'Admin/Brands/Index',
            props: new BrandsIndexProps,
            permission: 'catalog.view',
            order: 32,
        );

        $panel->page(
            key: 'shipping-methods',
            label: 'sivarcart::admin.shipping_methods.title',
            component: 'Admin/ShippingMethods/Index',
            props: new ShippingMethodsIndexProps,
            permission: 'catalog.view',
            order: 33,
        );

        $panel->page(
            key: 'payment-methods',
            label: 'sivarcart::admin.payment_methods.title',
            component: 'Admin/PaymentMethods/Index',
            props: new PaymentMethodsIndexProps,
            permission: 'catalog.view',
            order: 34,
        );

        $panel->page(
            key: 'customers',
            label: 'sivarcart::admin.customers.title',
            component: 'Admin/Customers/Index',
            props: new CustomersIndexProps,
            permission: 'customers.view',
            order: 40,
        );

        $panel->page(
            key: 'settings',
            label: 'sivarcart::admin.settings.title',
            component: 'Admin/Settings/Index',
            props: new SettingsIndexProps,
            permission: 'settings.view',
            order: 50,
        );

        $panel->page(
            key: 'roles',
            label: 'sivarcart::admin.roles.title',
            component: 'Admin/Roles/Index',
            props: new RolesIndexProps,
            permission: 'roles.view',
            order: 60,
        );

        $panel->page(
            key: 'staff',
            label: 'sivarcart::admin.staff.title',
            component: 'Admin/Staff/Index',
            props: new StaffIndexProps,
            permission: 'staff.view',
            order: 70,
        );
    }

    /**
     * Register the store the shopper buys in.
     *
     * The store is an Inertia application: its pages are components of React
     * and the bundle of the store travels with the package. The merchant
     * overrides them by publishing the source of the store and compiling its
     * own bundle.
     *
     * The store has its own root view and its own middleware of Inertia, in a
     * namespace of its own, so an application that already has an Inertia of
     * its own keeps it untouched.
     *
     * The store and the store API are two ways of mounting the same store and
     * they live together: the API serves the clients that are not a browser,
     * and the store serves the shopper that is one. Each one is turned off on
     * its own.
     */
    private function registerStorefront(): void
    {
        if (! (bool) config('sivarcart.storefront.enabled', true)) {
            return;
        }

        Route::aliasMiddleware('sivarcart.storefront.buyer', EnsureStorefrontBuyer::class);

        $this->loadRoutesFrom(__DIR__.'/../routes/storefront.php');
    }

    /**
     * Count the requests of every buyer on its own.
     *
     * The buyers that are not signed in share the limit of their IP.
     */
    private function registerApiRateLimiter(): void
    {
        RateLimiter::for('sivarcart', function (Request $request): Limit {
            $buyer = $this->app->make(CustomerService::class)->user();

            $key = $buyer instanceof Customer
                ? 'buyer:'.$buyer->getKey()
                : 'ip:'.$request->ip();

            return Limit::perMinute((int) config('sivarcart.api.rate_limit', 60))->by($key);
        });
    }

    /**
     * Answer the errors of the domain with JSON.
     *
     * The renderer is registered in the handler of the application, so the
     * routes of the package answer with the same shape as the rest of the
     * API of the host application.
     */
    private function registerApiExceptionRenderer(): void
    {
        /** @var object $handler */
        $handler = $this->app->make(ExceptionHandler::class);

        if (! method_exists($handler, 'renderable')) {
            return;
        }

        $handler->renderable(new ApiExceptionRenderer);
    }
}
