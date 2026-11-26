<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore;

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
use SivarcartCore\SivarcartCore\Admin\AdminPanel;
use SivarcartCore\SivarcartCore\Admin\Pages\CustomersIndexProps;
use SivarcartCore\SivarcartCore\Admin\Pages\DashboardProps;
use SivarcartCore\SivarcartCore\Admin\Pages\OrdersIndexProps;
use SivarcartCore\SivarcartCore\Admin\Pages\ProductsIndexProps;
use SivarcartCore\SivarcartCore\Admin\Pages\RolesIndexProps;
use SivarcartCore\SivarcartCore\Admin\Pages\SettingsIndexProps;
use SivarcartCore\SivarcartCore\Console\Commands\CreateAdminCommand;
use SivarcartCore\SivarcartCore\Console\Commands\PruneCartsCommand;
use SivarcartCore\SivarcartCore\Console\Commands\RestockCommand;
use SivarcartCore\SivarcartCore\Console\Commands\SivarcartCoreInstallCommand;
use SivarcartCore\SivarcartCore\Console\Commands\SivarcartCorePublishMailablesCommand;
use SivarcartCore\SivarcartCore\Contracts\PaymentGateway;
use SivarcartCore\SivarcartCore\Contracts\ShippingRateCalculator;
use SivarcartCore\SivarcartCore\Events\OrderCancelled;
use SivarcartCore\SivarcartCore\Events\OrderDelivered;
use SivarcartCore\SivarcartCore\Events\OrderPaid;
use SivarcartCore\SivarcartCore\Events\OrderPlaced;
use SivarcartCore\SivarcartCore\Events\OrderRefunded;
use SivarcartCore\SivarcartCore\Events\OrderShipped;
use SivarcartCore\SivarcartCore\Events\PaymentFailed;
use SivarcartCore\SivarcartCore\Http\ApiExceptionRenderer;
use SivarcartCore\SivarcartCore\Http\Middleware\EnsureBuyer;
use SivarcartCore\SivarcartCore\Http\Middleware\EnsureStoreAdmin;
use SivarcartCore\SivarcartCore\Http\Middleware\EnsureStorePermission;
use SivarcartCore\SivarcartCore\Http\Middleware\StorefrontApi;
use SivarcartCore\SivarcartCore\Listeners\SendOrderCancelledNotification;
use SivarcartCore\SivarcartCore\Listeners\SendOrderDeliveredNotification;
use SivarcartCore\SivarcartCore\Listeners\SendOrderPaidNotification;
use SivarcartCore\SivarcartCore\Listeners\SendOrderPlacedNotification;
use SivarcartCore\SivarcartCore\Listeners\SendOrderRefundedNotification;
use SivarcartCore\SivarcartCore\Listeners\SendOrderShippedNotification;
use SivarcartCore\SivarcartCore\Listeners\SendPaymentFailedNotification;
use SivarcartCore\SivarcartCore\Models\Customer;
use SivarcartCore\SivarcartCore\Models\ProductImage;
use SivarcartCore\SivarcartCore\Payments\PaymentGatewayManager;
use SivarcartCore\SivarcartCore\Services\Admin\CustomerService as AdminCustomerService;
use SivarcartCore\SivarcartCore\Services\Admin\OrderService as AdminOrderService;
use SivarcartCore\SivarcartCore\Services\Admin\ProductService as AdminProductService;
use SivarcartCore\SivarcartCore\Services\Admin\StatsService;
use SivarcartCore\SivarcartCore\Services\CartService;
use SivarcartCore\SivarcartCore\Services\CatalogService;
use SivarcartCore\SivarcartCore\Services\CouponService;
use SivarcartCore\SivarcartCore\Services\CustomerService;
use SivarcartCore\SivarcartCore\Services\DepartmentService;
use SivarcartCore\SivarcartCore\Services\InventoryService;
use SivarcartCore\SivarcartCore\Services\MediaService;
use SivarcartCore\SivarcartCore\Services\NotificationService;
use SivarcartCore\SivarcartCore\Services\OrderService;
use SivarcartCore\SivarcartCore\Services\PaymentMethodService;
use SivarcartCore\SivarcartCore\Services\PaymentService;
use SivarcartCore\SivarcartCore\Services\PricingService;
use SivarcartCore\SivarcartCore\Services\SequenceService;
use SivarcartCore\SivarcartCore\Services\ShippingMethodRateCalculator;
use SivarcartCore\SivarcartCore\Services\ShippingMethodService;
use SivarcartCore\SivarcartCore\Services\StoreService;
use SivarcartCore\SivarcartCore\Support\Auth;
use SivarcartCore\SivarcartCore\Support\Media;

class SivarcartCoreServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/sivarcart-core.php', 'sivarcart-core');

        $this->app->singleton(SivarcartCore::class);

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
        $guard = (string) config('sivarcart-core.buyers.guard', 'sivarcart');
        $provider = (string) config('sivarcart-core.buyers.provider', 'sivarcart');

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
                'sivarcart-core.buyers.password_reset_table',
                'sivarcart_password_reset_tokens',
            ),
            'expire' => (int) config('sivarcart-core.buyers.password_expire_minutes', 60),
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
        $this->loadRoutesFrom(__DIR__.'/../routes/sivarcart-core.php');

        $this->loadViewsFrom(__DIR__.'/../resources/views', 'sivarcart-core');

        /*
        | The root view of the panel lives in a namespace of its own, and not in
        | the namespace of the views of the package. The views of the package
        | are published and the merchant can put its own in
        | "resources/views/vendor/sivarcart-core", and the root view of an
        | Inertia application is not something to overwrite by accident: a
        | wrong file there breaks the mounting of the panel in silence.
        */
        $this->loadViewsFrom(__DIR__.'/../resources/views/panel', 'sivarcart-core-panel');

        /*
        | The pages of the panel live in the package, so Inertia has to know
        | where they are. The folder of the application is kept, so an
        | application that has an Inertia of its own keeps its own pages.
        */
        Config::set('inertia.pages.paths', [
            ...(array) Config::get('inertia.pages.paths', []),
            __DIR__.'/../resources/js/admin/pages',
        ]);

        $this->loadTranslationsFrom(__DIR__.'/../lang', 'sivarcart-core');

        $this->registerNotificationListeners();

        $this->registerStorePermissions();

        $this->registerStoreApi();

        $this->registerAdminPanel();

        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->publishes([
            __DIR__.'/../config/sivarcart-core.php' => config_path('sivarcart-core.php'),
        ], ['sivarcart-core', 'sivarcart-core-config']);

        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/sivarcart-core'),
        ], ['sivarcart-core', 'sivarcart-core-views']);

        /*
        | The root view of the panel is published on its own tag, and not with
        | the views of the package: it is the view that mounts the panel, so
        | the merchant that wants to change the head or the logo of the panel
        | asks for it explicitly.
        */
        $this->publishes([
            __DIR__.'/../resources/views/panel' => resource_path('views/vendor/sivarcart-core-panel'),
        ], ['sivarcart-core', 'sivarcart-core-panel-view']);

        /*
        | The source of the panel is published for the merchant that wants to
        | write its own interface. The published files are never overwritten,
        | so a customization survives an update of the package.
        */
        $this->publishes([
            __DIR__.'/../resources/js/admin' => resource_path('js/sivarcart'),
        ], ['sivarcart-core', 'sivarcart-core-admin-source']);

        $this->publishes([
            __DIR__.'/../resources/views/mail' => resource_path('views/vendor/sivarcart-core/mail'),
        ], ['sivarcart-core', 'sivarcart-core-mailables']);

        $this->publishes([
            __DIR__.'/../lang' => $this->app->langPath('vendor/sivarcart-core'),
        ], ['sivarcart-core', 'sivarcart-core-lang']);

        $this->publishes([
            __DIR__.'/../public' => public_path('vendor/sivarcart-core'),
        ], ['sivarcart-core', 'sivarcart-core-assets']);

        $this->publishes([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], ['sivarcart-core', 'sivarcart-core-migrations']);

        $this->commands([
            SivarcartCoreInstallCommand::class,
            SivarcartCorePublishMailablesCommand::class,
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

        if (! (bool) config('sivarcart-core.api.enabled', true)) {
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
        if (! (bool) config('sivarcart-core.admin.enabled', true)) {
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
        if (! (bool) config('sivarcart-core.admin.enabled', true)) {
            return;
        }

        $panel = $this->app->make(AdminPanel::class);

        $panel->page(
            key: 'dashboard',
            label: 'sivarcart-core::admin.dashboard.title',
            component: 'Admin/Dashboard',
            props: new DashboardProps,
            order: 10,
        );

        $panel->page(
            key: 'orders',
            label: 'sivarcart-core::admin.orders.title',
            component: 'Admin/Orders/Index',
            props: new OrdersIndexProps,
            permission: 'orders.view',
            order: 20,
        );

        $panel->page(
            key: 'products',
            label: 'sivarcart-core::admin.products.title',
            component: 'Admin/Products/Index',
            props: new ProductsIndexProps,
            permission: 'catalog.view',
            order: 30,
        );

        $panel->page(
            key: 'customers',
            label: 'sivarcart-core::admin.customers.title',
            component: 'Admin/Customers/Index',
            props: new CustomersIndexProps,
            permission: 'customers.view',
            order: 40,
        );

        $panel->page(
            key: 'settings',
            label: 'sivarcart-core::admin.settings.title',
            component: 'Admin/Settings/Index',
            props: new SettingsIndexProps,
            permission: 'settings.view',
            order: 50,
        );

        $panel->page(
            key: 'roles',
            label: 'sivarcart-core::admin.roles.title',
            component: 'Admin/Roles/Index',
            props: new RolesIndexProps,
            permission: 'roles.view',
            order: 60,
        );
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

            return Limit::perMinute((int) config('sivarcart-core.api.rate_limit', 60))->by($key);
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
