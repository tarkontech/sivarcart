<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use SivarcartCore\SivarcartCore\Exceptions\SivarcartCoreException;
use SivarcartCore\SivarcartCore\Models\Product;
use SivarcartCore\SivarcartCore\Tests\Support\CheckoutScenario;
use Workbench\App\Models\User;

it('answers with JSON even when the client does not ask for it', function () {
    $this->get('/api/sivarcart/cart')
        ->assertUnauthorized()
        ->assertHeader('content-type', 'application/json')
        ->assertJsonPath('code', 'customer_required');
});

it('answers with a 401 when the buyer is not signed in', function () {
    $this->getJson('/api/sivarcart/cart')
        ->assertUnauthorized()
        ->assertJsonPath('message', 'El comprador tiene que iniciar sesión para completar la orden.')
        ->assertJsonPath('code', 'customer_required');
});

it('answers with a 422 when the domain refuses the operation', function () {
    $scenario = CheckoutScenario::buyer();
    $this->actingAs($scenario->customer, 'sivarcart');

    $this->postJson('/api/sivarcart/cart/coupon', ['code' => 'NO-EXISTE'])
        ->assertStatus(422)
        ->assertJsonPath('code', 'coupon_not_applicable');
});

it('answers with a 409 when the state of the cart does not allow the operation', function () {
    $scenario = CheckoutScenario::buyer();
    $this->actingAs($scenario->customer, 'sivarcart');

    $scenario->cart->items()->delete();

    $this->postJson('/api/sivarcart/checkout', [
        'customer_name' => 'Ana Guzmán',
        'shipping_address' => [
            'address_line1' => 'Calle 1',
            'municipality' => 'San Salvador',
        ],
    ])
        ->assertStatus(409)
        ->assertJsonPath('code', 'invalid_cart_state');
});

it('answers with a 422 when there are no units available', function () {
    $scenario = CheckoutScenario::buyer();
    $this->actingAs($scenario->customer, 'sivarcart');

    $scenario->variant->update(['stock_quantity' => 0, 'reserved_quantity' => 0]);

    $this->postJson('/api/sivarcart/checkout', [
        'customer_name' => 'Ana Guzmán',
        'shipping_address' => [
            'address_line1' => 'Calle 1',
            'municipality' => 'San Salvador',
        ],
    ])
        ->assertStatus(422)
        ->assertJsonPath('code', 'insufficient_stock');
});

it('answers with a 404 when the resource does not exist', function () {
    $this->getJson('/api/sivarcart/products/00000000-0000-0000-0000-000000000000')
        ->assertNotFound();
});

it('leaves the answers of the application alone', function () {
    Route::get('/ruta-de-la-aplicacion', function (): void {
        throw new SivarcartCoreException('Un error del dominio.');
    });

    $this->get('/ruta-de-la-aplicacion')->assertStatus(500);
});

it('does not answer the catalog of another store', function () {
    $product = Product::factory()->create();

    $this->getJson('/api/sivarcart/products')
        ->assertOk()
        ->assertJsonPath('data.0.id', $product->uuid);
});

it('keeps the buyer and the staff of the store apart', function () {
    $this->actingAs(User::factory()->create(), 'web');

    $this->getJson('/api/sivarcart/account')->assertUnauthorized();
});
