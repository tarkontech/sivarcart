<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
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
use SivarcartCore\SivarcartCore\Http\Middleware\EnsureStorePermission;
use SivarcartCore\SivarcartCore\Listeners\SendOrderCancelledNotification;
use SivarcartCore\SivarcartCore\Listeners\SendOrderDeliveredNotification;
use SivarcartCore\SivarcartCore\Listeners\SendOrderPaidNotification;
use SivarcartCore\SivarcartCore\Listeners\SendOrderPlacedNotification;
use SivarcartCore\SivarcartCore\Listeners\SendOrderRefundedNotification;
use SivarcartCore\SivarcartCore\Listeners\SendOrderShippedNotification;
use SivarcartCore\SivarcartCore\Listeners\SendPaymentFailedNotification;
use SivarcartCore\SivarcartCore\Payments\PaymentGatewayManager;
use SivarcartCore\SivarcartCore\Services\CartService;
use SivarcartCore\SivarcartCore\Services\CouponService;
use SivarcartCore\SivarcartCore\Services\InventoryService;
use SivarcartCore\SivarcartCore\Services\NotificationService;
use SivarcartCore\SivarcartCore\Services\OrderService;
use SivarcartCore\SivarcartCore\Services\PaymentService;
use SivarcartCore\SivarcartCore\Services\PricingService;
use SivarcartCore\SivarcartCore\Services\SequenceService;
use SivarcartCore\SivarcartCore\Services\ShippingMethodRateCalculator;
use SivarcartCore\SivarcartCore\Services\StoreService;
use SivarcartCore\SivarcartCore\Support\Auth;

class SivarcartCoreServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/sivarcart-core.php', 'sivarcart-core');

        $this->app->singleton(SivarcartCore::class);

        $this->registerServices();
        $this->registerContracts();
    }

    /**
     * Register the services of the domain.
     */
    private function registerServices(): void
    {
        foreach ([
            StoreService::class,
            SequenceService::class,
            PricingService::class,
            CouponService::class,
            InventoryService::class,
            CartService::class,
            OrderService::class,
            PaymentService::class,
            PaymentGatewayManager::class,
            NotificationService::class,
        ] as $service) {
            $this->app->singleton($service);
        }
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

        $this->loadTranslationsFrom(__DIR__.'/../lang', 'sivarcart-core');

        $this->registerNotificationListeners();

        $this->registerStorePermissions();

        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->publishes([
            __DIR__.'/../config/sivarcart-core.php' => config_path('sivarcart-core.php'),
        ], ['sivarcart-core', 'sivarcart-core-config']);

        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/sivarcart-core'),
        ], ['sivarcart-core', 'sivarcart-core-views']);

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
        ]);
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
}
