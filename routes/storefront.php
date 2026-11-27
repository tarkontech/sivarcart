<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use SivarcartCore\SivarcartCore\Http\Controllers\Storefront\AccountController;
use SivarcartCore\SivarcartCore\Http\Controllers\Storefront\CartController;
use SivarcartCore\SivarcartCore\Http\Controllers\Storefront\CatalogController;
use SivarcartCore\SivarcartCore\Http\Controllers\Storefront\CheckoutController;
use SivarcartCore\SivarcartCore\Http\Controllers\Storefront\OrderController;
use SivarcartCore\SivarcartCore\Http\Controllers\Storefront\StorefrontAssetController;
use SivarcartCore\SivarcartCore\Http\Middleware\HandleStorefrontInertiaRequests;

/*
|--------------------------------------------------------------------------
| Tienda del comprador
|--------------------------------------------------------------------------
|
| La tienda es la pantalla donde el cliente compra. Es una aplicación de
| Inertia y React que vive en el paquete, con su propio bundle ya compilado,
| así que el comerciante instala el paquete y la tienda está.
|
| Sus rutas las registra el paquete, así que la aplicación no tiene que
| publicarlas. El prefijo y el middleware vienen de la sección
| "storefront" de la configuración del paquete.
|
| La tienda es una aplicación de Inertia de su propia: su middleware corre
| después del middleware de la aplicación, así que las props de la tienda
| son las que llegan a sus páginas, y una aplicación que ya tiene un Inertia
| de su propio lo mantiene intacto.
|
| Los segmentos de las direcciones son los que el comprador ve, así que
| están en español. Los nombres de las rutas son del paquete y están en
| inglés.
|
*/

/*
| Los assets y las pantallas públicas no necesitan un comprador firmado: la
| pantalla de entrada necesita los assets, y el catálogo es público.
|
| Los assets viven en una carpeta con la versión del bundle. Las piezas del
| bundle se piden entre ellas con una dirección relativa, así que la versión
| de la carpeta viaja con ellas: un navegador que guardó el bundle de una
| versión anterior nunca lo mezcla con el de la que está instalada.
*/
$prefix = (string) config('sivarcart-core.storefront.prefix', 'tienda');

$web = (array) config('sivarcart-core.storefront.middleware', ['web']);

Route::prefix($prefix)->middleware($web)->group(function (): void {
    Route::get('assets/{version}/storefront.css', [StorefrontAssetController::class, 'css'])
        ->where('version', '[A-Za-z0-9]+')
        ->name('sivarcart-core.storefront.assets.css');

    Route::get('assets/{version}/storefront.js', [StorefrontAssetController::class, 'js'])
        ->where('version', '[A-Za-z0-9]+')
        ->name('sivarcart-core.storefront.assets.js');

    /*
    | El bundle de la tienda está partido en piezas, una por página, y el
    | navegador las pide por su nombre.
    */
    Route::get('assets/{version}/{chunk}', [StorefrontAssetController::class, 'chunk'])
        ->where('version', '[A-Za-z0-9]+')
        ->where('chunk', '[A-Za-z0-9_.-]+\.(js|css)')
        ->name('sivarcart-core.storefront.assets.chunk');
});

Route::prefix($prefix)
    ->middleware(array_merge($web, [HandleStorefrontInertiaRequests::class]))
    ->group(function (): void {
        /*
        | El catálogo es público: cualquiera puede leer los productos, las
        | categorías y las marcas de la tienda.
        */
        Route::get('/', [CatalogController::class, 'home'])
            ->name('sivarcart-core.storefront.home');

        Route::get('productos', [CatalogController::class, 'index'])
            ->name('sivarcart-core.storefront.products.index');

        Route::get('productos/{product}', [CatalogController::class, 'show'])
            ->name('sivarcart-core.storefront.products.show');

        /*
        | La cuenta del comprador. El registro y la entrada no necesitan un
        | comprador firmado: son las que abren la sesión.
        */
        Route::get('cuenta/entrar', [AccountController::class, 'login'])
            ->name('sivarcart-core.storefront.account.login');

        Route::post('cuenta/entrar', [AccountController::class, 'loginStore'])
            ->name('sivarcart-core.storefront.account.login.store');

        Route::get('cuenta/registro', [AccountController::class, 'register'])
            ->name('sivarcart-core.storefront.account.register');

        Route::post('cuenta/registro', [AccountController::class, 'registerStore'])
            ->name('sivarcart-core.storefront.account.register.store');

        Route::post('cuenta/salir', [AccountController::class, 'logout'])
            ->name('sivarcart-core.storefront.account.logout');

        /*
        | El restablecimiento de la clave: la tienda dibuja esas pantallas,
        | así que el link del correo apunta aquí.
        */
        Route::get('cuenta/recuperar', [AccountController::class, 'passwordRequest'])
            ->name('sivarcart-core.storefront.account.password.request');

        Route::post('cuenta/recuperar', [AccountController::class, 'passwordEmail'])
            ->name('sivarcart-core.storefront.account.password.email');

        Route::get('cuenta/recuperar/{token}', [AccountController::class, 'passwordReset'])
            ->name('sivarcart-core.storefront.account.password.reset');

        Route::post('cuenta/recuperar/{token}', [AccountController::class, 'passwordUpdate'])
            ->name('sivarcart-core.storefront.account.password.reset.store');

        /*
        | El carrito del comprador. El carrito siempre pertenece a un
        | comprador, así que cada ruta del grupo necesita un comprador
        | firmado.
        */
        Route::middleware('sivarcart.storefront.buyer')->group(function (): void {
            Route::get('carrito', [CartController::class, 'show'])
                ->name('sivarcart-core.storefront.cart.show');

            Route::post('carrito/items', [CartController::class, 'addItem'])
                ->name('sivarcart-core.storefront.cart.items.store');

            Route::patch('carrito/items/{item}', [CartController::class, 'updateItem'])
                ->name('sivarcart-core.storefront.cart.items.update');

            Route::delete('carrito/items/{item}', [CartController::class, 'removeItem'])
                ->name('sivarcart-core.storefront.cart.items.destroy');

            Route::post('carrito/cupon', [CartController::class, 'applyCoupon'])
                ->name('sivarcart-core.storefront.cart.coupon.store');

            Route::delete('carrito/cupon', [CartController::class, 'removeCoupon'])
                ->name('sivarcart-core.storefront.cart.coupon.destroy');

            Route::delete('carrito', [CartController::class, 'clear'])
                ->name('sivarcart-core.storefront.cart.destroy');

            /*
            | El checkout: las opciones de la tienda, el precio del envío y la
            | orden misma.
            */
            Route::get('checkout', [CheckoutController::class, 'show'])
                ->name('sivarcart-core.storefront.checkout.show');

            Route::post('checkout/envio', [CheckoutController::class, 'shippingQuote'])
                ->name('sivarcart-core.storefront.checkout.shipping-quote');

            Route::post('checkout', [CheckoutController::class, 'store'])
                ->name('sivarcart-core.storefront.checkout.store');

            /*
            | Las órdenes del comprador.
            */
            Route::get('pedidos', [OrderController::class, 'index'])
                ->name('sivarcart-core.storefront.orders.index');

            Route::get('pedidos/{order}', [OrderController::class, 'show'])
                ->name('sivarcart-core.storefront.orders.show');

            Route::post('pedidos/{order}/pagar', [OrderController::class, 'payment'])
                ->name('sivarcart-core.storefront.orders.payment');

            /*
            | La cuenta del comprador que ya entró: su perfil y su clave.
            */
            Route::get('cuenta', [AccountController::class, 'show'])
                ->name('sivarcart-core.storefront.account.show');

            Route::put('cuenta', [AccountController::class, 'update'])
                ->name('sivarcart-core.storefront.account.update');

            Route::put('cuenta/clave', [AccountController::class, 'changePassword'])
                ->name('sivarcart-core.storefront.account.password.change');
        });
    });
