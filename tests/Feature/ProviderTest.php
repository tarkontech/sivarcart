<?php

declare(strict_types=1);

use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Tarkontech\SivarCart\Contracts\PaymentGateway;
use Tarkontech\SivarCart\Contracts\ShippingRateCalculator;
use Tarkontech\SivarCart\Data\ShippingQuote;
use Tarkontech\SivarCart\Http\Middleware\EnsureBuyer;
use Tarkontech\SivarCart\Http\Middleware\EnsureStorePermission;
use Tarkontech\SivarCart\Http\Middleware\StorefrontApi;
use Tarkontech\SivarCart\Models\Cart;
use Tarkontech\SivarCart\Models\ProductImage;
use Tarkontech\SivarCart\Models\ShippingMethod;
use Tarkontech\SivarCart\Payments\WompiPaymentGateway;
use Tarkontech\SivarCart\Services\CartService;
use Tarkontech\SivarCart\Services\CatalogService;
use Tarkontech\SivarCart\Services\CustomerService;
use Tarkontech\SivarCart\Services\DepartmentService;
use Tarkontech\SivarCart\Services\InventoryService;
use Tarkontech\SivarCart\Services\MediaService;
use Tarkontech\SivarCart\Services\OrderService;
use Tarkontech\SivarCart\Services\PaymentMethodService;
use Tarkontech\SivarCart\Services\PaymentService;
use Tarkontech\SivarCart\Services\ShippingMethodRateCalculator;
use Tarkontech\SivarCart\Services\ShippingMethodService;
use Tarkontech\SivarCart\SivarCartServiceProvider;

it('publishes the migrations under the documented tag', function () {
    Artisan::call('vendor:publish', ['--tag' => 'sivarcart-migrations']);

    expect(File::exists(database_path('migrations').'/2026_01_01_000001_create_sivarcart_departments_table.php'))->toBeTrue();
    expect(File::exists(database_path('migrations').'/2026_01_01_000030_create_sivarcart_payment_transactions_table.php'))->toBeTrue();
    expect(File::exists(database_path('migrations').'/2026_01_01_000037_add_media_columns_to_sivarcart_product_images_table.php'))->toBeTrue();
});

it('never overwrites a migration the merchant already published', function () {
    Artisan::call('vendor:publish', ['--tag' => 'sivarcart-migrations']);

    $file = database_path('migrations').'/2026_01_01_000001_create_sivarcart_departments_table.php';

    File::put($file, File::get($file)."\n// Personalización del comerciante.\n");

    Artisan::call('vendor:publish', ['--tag' => 'sivarcart-migrations']);

    expect(File::get($file))->toContain('// Personalización del comerciante.');
});

it('does not publish a second copy of an already published migration', function () {
    Artisan::call('vendor:publish', ['--tag' => 'sivarcart-migrations']);

    Artisan::call('vendor:publish', ['--tag' => 'sivarcart-migrations']);

    $files = File::glob(database_path('migrations').'/*_create_sivarcart_departments_table.php');

    expect($files)->toHaveCount(1);
});

it('registers the configuration file to publish', function () {
    $paths = ServiceProvider::pathsToPublish(SivarCartServiceProvider::class, 'sivarcart-config');

    expect($paths)->toHaveCount(1);

    $source = (string) array_key_first($paths);
    $destination = (string) reset($paths);

    expect(realpath($source))->toBe(realpath(__DIR__.'/../../config/sivarcart.php'));
    expect($destination)->toBe(config_path('sivarcart.php'));
});

it('registers the publish groups of the package', function () {
    $groups = ServiceProvider::publishableGroups();

    expect($groups)->toContain('sivarcart');
    expect($groups)->toContain('sivarcart-config');
    expect($groups)->toContain('sivarcart-migrations');
    expect($groups)->toContain('sivarcart-views');
    expect($groups)->toContain('sivarcart-lang');
    expect($groups)->toContain('sivarcart-assets');
});

it('merges the package configuration into the application', function () {
    expect(config('sivarcart.wompi.webhook_path'))->toBe('webhooks/wompi');
    expect(config('sivarcart.wompi.payment_link.permitir_tarjeta_credito_debito'))->toBeTrue();
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
        ->and(app(CatalogService::class))->toBeInstanceOf(CatalogService::class)
        ->and(app(PaymentMethodService::class))->toBeInstanceOf(PaymentMethodService::class)
        ->and(app(ShippingMethodService::class))->toBeInstanceOf(ShippingMethodService::class)
        ->and(app(DepartmentService::class))->toBeInstanceOf(DepartmentService::class);
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
    expect(Route::has('sivarcart.wompi.webhook'))->toBeTrue();
});

it('registers the middleware of the permissions of the store', function () {
    expect(app('router')->getMiddleware())->toHaveKey('store.permission', EnsureStorePermission::class);
});

it('registers the middleware of the store API', function () {
    expect(app('router')->getMiddleware())->toHaveKey('sivarcart.api', StorefrontApi::class)
        ->and(app('router')->getMiddleware())->toHaveKey('sivarcart.buyer', EnsureBuyer::class);
});

it('merges the section of the API into the configuration of the application', function () {
    expect(config('sivarcart.api.enabled'))->toBeTrue()
        ->and(config('sivarcart.api.prefix'))->toBe('api/sivarcart')
        ->and(config('sivarcart.api.middleware'))->toBe(['api'])
        ->and(config('sivarcart.api.rate_limit'))->toBe(60);
});

it('registers the command that prunes the carts', function () {
    expect(Artisan::all())->toHaveKey('sivarcart:prune-carts');
});

it('registers the command that restocks a variant', function () {
    expect(Artisan::all())->toHaveKey('sivarcart:restock');
});

it('schedules the pruning of the carts once a day', function () {
    $pruning = collect(app(Schedule::class)->events())
        ->first(fn (Event $event): bool => str_contains((string) $event->command, 'sivarcart:prune-carts'));

    expect($pruning)->not->toBeNull()
        ->and($pruning->getExpression())->toBe('0 0 * * *');
});

it('defines the ability of the permissions of the store', function () {
    expect(Gate::has('store-permission'))->toBeTrue();
});

it('merges the section of the media into the configuration of the application', function () {
    expect(config('sivarcart.media.disk'))->toBe('public')
        ->and(config('sivarcart.media.max_size'))->toBe(10240)
        ->and(config('sivarcart.media.mimes'))->toBe(['jpg', 'jpeg', 'png', 'webp'])
        ->and(config('sivarcart.media.queue_conversions'))->toBeFalse()
        ->and(config('sivarcart.media.thumbnails'))->toBe([
            'thumb' => ['width' => 300, 'height' => 300],
            'medium' => ['width' => 800, 'height' => 800],
        ]);
});

it('tells the media library how the package stores its files', function () {
    expect(config('media-library.media_model'))->toBe(ProductImage::class)
        ->and(config('media-library.disk_name'))->toBe('public')
        ->and(config('media-library.max_file_size'))->toBe(10240 * 1024)
        ->and(config('media-library.allowed_extensions'))->toBe(['jpg', 'jpeg', 'png', 'webp'])
        ->and(config('media-library.queue_conversions_by_default'))->toBeFalse();
});

it('registers the service of the media', function () {
    expect(app(MediaService::class))->toBe(app(MediaService::class));
});
