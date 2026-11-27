<?php

declare(strict_types=1);

use Inertia\Testing\AssertableInertia as Assert;
use SivarcartCore\SivarcartCore\Models\Cart;
use SivarcartCore\SivarcartCore\Models\Coupon;
use SivarcartCore\SivarcartCore\Models\Product;

it('shows the cart of the shopper', function () {
    shopper();

    $this->get('/tienda/carrito')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Storefront/Cart/Index')
            ->where('cart.item_count', 0)
            ->where('cart.total', 0)
            ->has('cart.items', 0),
        );
});

it('puts a variant in the cart', function () {
    shopper();

    $variant = Product::factory()->create()->defaultVariant();

    $this->post('/tienda/carrito/items', [
        'variant_id' => $variant->uuid,
        'quantity' => 2,
    ])->assertRedirect();

    expect(Cart::query()->firstOrFail()->items()->firstOrFail()->quantity)->toBe(2);
});

it('answers 404 for a variant that does not exist', function () {
    shopper();

    $this->post('/tienda/carrito/items', ['variant_id' => 'no-existe'])->assertNotFound();
});

it('answers 404 for a variant of a product that is not published', function () {
    shopper();

    $variant = Product::factory()->create(['status' => 'draft'])->defaultVariant();

    $this->post('/tienda/carrito/items', ['variant_id' => $variant->uuid])->assertNotFound();
});

it('changes the units of a line of the cart', function () {
    shopper();

    $variant = Product::factory()->create()->defaultVariant();

    $this->post('/tienda/carrito/items', ['variant_id' => $variant->uuid]);

    $line = Cart::query()->firstOrFail()->items()->firstOrFail();

    $this->patch('/tienda/carrito/items/'.$line->getKey(), ['quantity' => 5])
        ->assertRedirect();

    expect($line->fresh()->quantity)->toBe(5);
});

it('takes a line out of the cart', function () {
    shopper();

    $variant = Product::factory()->create()->defaultVariant();

    $this->post('/tienda/carrito/items', ['variant_id' => $variant->uuid]);

    $line = Cart::query()->firstOrFail()->items()->firstOrFail();

    $this->delete('/tienda/carrito/items/'.$line->getKey())->assertRedirect();

    expect(Cart::query()->firstOrFail()->items()->count())->toBe(0);
});

it('applies a discount code to the cart', function () {
    shopper();

    Coupon::factory()->create(['code' => 'BIENVENIDO']);

    $this->post('/tienda/carrito/cupon', ['code' => 'BIENVENIDO'])->assertRedirect();

    expect(Cart::query()->firstOrFail()->coupon)->not->toBeNull();
});

it('says so when the discount code does not exist', function () {
    shopper();

    $this->post('/tienda/carrito/cupon', ['code' => 'NO-EXISTE'])
        ->assertRedirect()
        ->assertSessionHasErrors('code');
});

it('takes the discount code out of the cart', function () {
    shopper();

    Coupon::factory()->create(['code' => 'BIENVENIDO']);

    $this->post('/tienda/carrito/cupon', ['code' => 'BIENVENIDO']);

    $this->delete('/tienda/carrito/cupon')->assertRedirect();

    expect(Cart::query()->firstOrFail()->coupon)->toBeNull();
});

it('empties the cart of the shopper', function () {
    shopper();

    $variant = Product::factory()->create()->defaultVariant();

    $this->post('/tienda/carrito/items', ['variant_id' => $variant->uuid]);

    $this->delete('/tienda/carrito')->assertRedirect();

    expect(Cart::query()->firstOrFail()->items()->count())->toBe(0);
});

it('shows the number of the cart in el header', function () {
    shopper();

    $variant = Product::factory()->create()->defaultVariant();

    $this->post('/tienda/carrito/items', ['variant_id' => $variant->uuid]);

    $this->get('/tienda')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('cart.count', 1));
});
