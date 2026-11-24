<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use SivarcartCore\SivarcartCore\Contracts\PaymentGateway;
use SivarcartCore\SivarcartCore\Contracts\ShippingRateCalculator;
use SivarcartCore\SivarcartCore\Data\ShippingQuote;
use SivarcartCore\SivarcartCore\Http\Middleware\EnsureStorePermission;
use SivarcartCore\SivarcartCore\Models\Cart;
use SivarcartCore\SivarcartCore\Models\ShippingMethod;
use SivarcartCore\SivarcartCore\Payments\WompiPaymentGateway;
use SivarcartCore\SivarcartCore\Services\CartService;
use SivarcartCore\SivarcartCore\Services\CatalogService;
use SivarcartCore\SivarcartCore\Services\CustomerService;
use SivarcartCore\SivarcartCore\Services\InventoryService;
use SivarcartCore\SivarcartCore\Services\OrderService;
use SivarcartCore\SivarcartCore\Services\PaymentService;
use SivarcartCore\SivarcartCore\Services\ShippingMethodRateCalculator;
use SivarcartCore\SivarcartCore\SivarcartCoreServiceProvider;

it('publishes the migrations under the documented tag', function () {
    Artisan::call('vendor:publish', ['--tag' => 'sivarcart-core-migrations']);

    expect(File::exists(database_path('migrations').'/2026_01_01_000001_create_sivarcart_departments_table.php'))->toBeTrue();
    expect(File::exists(database_path('migrations').'/2026_01_01_000030_create_sivarcart_payment_transactions_table.php'))->toBeTrue();
});

it('never overwrites a migration the merchant already published', function () {
    Artisan::call('vendor:publish', ['--tag' => 'sivarcart-core-migrations']);

    $file = database_path('migrations').'/2026_01_01_000001_create_sivarcart_departments_table.php';

    File::put($file, File::get($file)."\n// Personalización del comerciante.\n");

    Artisan::call('vendor:publish', ['--tag' => 'sivarcart-core-migrations']);

    expect(File::get($file))->toContain('// Personalización del comerciante.');
});

it('does not publish a second copy of an already published migration', function () {
    Artisan::call('vendor:publish', ['--tag' => 'sivarcart-core-migrations']);

    Artisan::call('vendor:publish', ['--tag' => 'sivarcart-core-migrations']);

    $files = File::glob(database_path('migrations').'/*_create_sivarcart_departments_table.php');

    expect($files)->toHaveCount(1);
});

it('registers the configuration file to publish', function () {
    $paths = ServiceProvider::pathsToPublish(SivarcartCoreServiceProvider::class, 'sivarcart-core-config');

    expect($paths)->toHaveCount(1);

    $source = (string) array_key_first($paths);
    $destination = (string) reset($paths);

    expect(realpath($source))->toBe(realpath(__DIR__.'/../../config/sivarcart-core.php'));
    expect($destination)->toBe(config_path('sivarcart-core.php'));
});

it('registers the publish groups of the package', function () {
    $groups = ServiceProvider::publishableGroups();

    expect($groups)->toContain('sivarcart-core');
    expect($groups)->toContain('sivarcart-core-config');
    expect($groups)->toContain('sivarcart-core-migrations');
    expect($groups)->toContain('sivarcart-core-views');
    expect($groups)->toContain('sivarcart-core-lang');
    expect($groups)->toContain('sivarcart-core-assets');
});

it('merges the package configuration into the application', function () {
    expect(config('sivarcart-core.wompi.webhook_path'))->toBe('webhooks/wompi');
    expect(config('sivarcart-core.wompi.payment_link.permitir_tarjeta_credito_debito'))->toBeTrue();
});

it('does not load the migrations from the package', function () {
    expect(app('migrator')->paths())->not->toContain(realpath(__DIR__.'/../../database/migrations'));
});

it('registers the services of the domain', function () {
    expect(app(CartService::class))->toBeInstanceOf(CartService::class)
        ->and(app(CustomerService::class))->toBeInstanceOf(CustomerService::class)
        ->and(app(OrderService::class))->toBeInstanceOf(OrderService::class)
        ->and(app(PaymentService::class))->toBeInstanceOf(PaymentService::class)
        ->and(app(InventoryService::class))->toBeInstanceOf(InventoryService::class)
        ->and(app(CatalogService::class))->toBeInstanceOf(CatalogService::class);
});

it('resolves the shipping rate calculator of the package', function () {
    expect(app(ShippingRateCalculator::class))->toBeInstanceOf(ShippingMethodRateCalculator::class);
});

it('resolves the payment gateway of the package', function () {
    expect(app(PaymentGateway::class))->toBeInstanceOf(WompiPaymentGateway::class);
});

it('resolves the same instance of every service', function () {
    expect(app(CartService::class))->toBe(app(CartService::class))
        ->and(app(OrderService::class))->toBe(app(OrderService::class))
        ->and(app(CatalogService::class))->toBe(app(CatalogService::class));
});

it('lets a module replace the shipping rate calculator', function () {
    $calculator = new class implements ShippingRateCalculator
    {
        public function quote(ShippingMethod $method, Cart $cart, array $shippingAddress): ShippingQuote
        {
            return new ShippingQuote(0.0);
        }
    };

    app()->bind(ShippingRateCalculator::class, fn (): ShippingRateCalculator => $calculator);

    expect(app(ShippingRateCalculator::class))->toBe($calculator);
});

it('registers the route of the webhook of Wompi', function () {
    expect(Route::has('sivarcart-core.wompi.webhook'))->toBeTrue();
});

it('registers the middleware of the permissions of the store', function () {
    expect(app('router')->getMiddleware())->toHaveKey('store.permission', EnsureStorePermission::class);
});

it('defines the ability of the permissions of the store', function () {
    expect(Gate::has('store-permission'))->toBeTrue();
});
