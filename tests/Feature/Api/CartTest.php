<?php

declare(strict_types=1);

use SivarcartCore\SivarcartCore\Models\Cart;
use SivarcartCore\SivarcartCore\Models\Coupon;
use SivarcartCore\SivarcartCore\Models\Customer;
use SivarcartCore\SivarcartCore\Models\Product;
use SivarcartCore\SivarcartCore\Models\Store;
use SivarcartCore\SivarcartCore\Services\CartService;

/**
 * A buyer with a session and a store to buy in.
 */
function buyer(): Customer
{
    Store::factory()->create();

    $customer = Customer::factory()->create();

    test()->actingAs($customer, 'sivarcart');

    return $customer;
}

it('asks for a buyer', function () {
    $this->getJson('/api/sivarcart/cart')
        ->assertUnauthorized()
        ->assertJsonPath('code', 'customer_required');
});

it('shows the cart of the buyer', function () {
    buyer();

    $this->getJson('/api/sivarcart/cart')
        ->assertOk()
        ->assertJsonPath('data.item_count', 0)
        ->assertJsonPath('data.total', 0)
        ->assertJsonCount(0, 'data.items');
});

it('puts a variant in the cart', function () {
    buyer();

    $variant = Product::factory()->create()->defaultVariant();

    $this->postJson('/api/sivarcart/cart/items', [
        'variant_id' => $variant->getKey(),
        'quantity' => 2,
    ])
        ->assertCreated()
        ->assertJsonPath('data.item_count', 2)
        ->assertJsonPath('data.items.0.product_variant_id', $variant->getKey())
        ->assertJsonPath('data.items.0.quantity', 2)
        ->assertJsonPath('data.total', 51);
});

it('does not put a variant that is not sold in the cart', function () {
    buyer();

    $variant = Product::factory()->create()->defaultVariant();
    $variant->update(['is_active' => false]);

    $this->postJson('/api/sivarcart/cart/items', ['variant_id' => $variant->getKey()])
        ->assertNotFound();
});

it('changes the units of a line of the cart', function () {
    buyer();

    $variant = Product::factory()->create()->defaultVariant();

    $this->postJson('/api/sivarcart/cart/items', ['variant_id' => $variant->getKey()])
        ->assertCreated();

    $line = Cart::query()->firstOrFail()->items()->firstOrFail();

    $this->patchJson('/api/sivarcart/cart/items/'.$line->getKey(), ['quantity' => 4])
        ->assertOk()
        ->assertJsonPath('data.items.0.quantity', 4)
        ->assertJsonPath('data.total', 102);
});

it('takes a line out of the cart when it has no units left', function () {
    buyer();

    $variant = Product::factory()->create()->defaultVariant();

    $this->postJson('/api/sivarcart/cart/items', ['variant_id' => $variant->getKey()])
        ->assertCreated();

    $line = Cart::query()->firstOrFail()->items()->firstOrFail();

    $this->patchJson('/api/sivarcart/cart/items/'.$line->getKey(), ['quantity' => 0])
        ->assertOk()
        ->assertJsonCount(0, 'data.items');
});

it('takes a line out of the cart', function () {
    buyer();

    $variant = Product::factory()->create()->defaultVariant();

    $this->postJson('/api/sivarcart/cart/items', ['variant_id' => $variant->getKey()])
        ->assertCreated();

    $line = Cart::query()->firstOrFail()->items()->firstOrFail();

    $this->deleteJson('/api/sivarcart/cart/items/'.$line->getKey())
        ->assertOk()
        ->assertJsonCount(0, 'data.items');
});

it('does not touch a line of another buyer', function () {
    $other = buyer();

    $variant = Product::factory()->create()->defaultVariant();

    $this->postJson('/api/sivarcart/cart/items', ['variant_id' => $variant->getKey()])
        ->assertCreated();

    $line = Cart::query()->firstOrFail()->items()->firstOrFail();

    $stranger = Customer::factory()->create();
    $this->actingAs($stranger, 'sivarcart');

    $this->patchJson('/api/sivarcart/cart/items/'.$line->getKey(), ['quantity' => 9])
        ->assertNotFound();

    expect($line->fresh()->quantity)->toBe(1);
});

it('applies a discount code to the cart', function () {
    buyer();

    Coupon::factory()->percentage(10)->create(['code' => 'DIEZ']);

    $variant = Product::factory()->create()->defaultVariant();

    $this->postJson('/api/sivarcart/cart/items', ['variant_id' => $variant->getKey()])
        ->assertCreated();

    $this->postJson('/api/sivarcart/cart/coupon', ['code' => 'DIEZ'])
        ->assertOk()
        ->assertJsonPath('data.coupon', 'DIEZ')
        ->assertJsonPath('data.discount_total', 2.55);
});

it('refuses a discount code that does not exist', function () {
    buyer();

    $this->postJson('/api/sivarcart/cart/coupon', ['code' => 'NO-EXISTE'])
        ->assertStatus(422)
        ->assertJsonPath('code', 'coupon_not_applicable');
});

it('takes the discount code out of the cart', function () {
    buyer();

    Coupon::factory()->percentage(10)->create(['code' => 'DIEZ']);

    $variant = Product::factory()->create()->defaultVariant();

    $this->postJson('/api/sivarcart/cart/items', ['variant_id' => $variant->getKey()])
        ->assertCreated();

    $this->postJson('/api/sivarcart/cart/coupon', ['code' => 'DIEZ'])->assertOk();

    $this->deleteJson('/api/sivarcart/cart/coupon')
        ->assertOk()
        ->assertJsonPath('data.coupon', null)
        ->assertJsonPath('data.discount_total', 0);
});

it('empties the cart of the buyer', function () {
    buyer();

    $variant = Product::factory()->create()->defaultVariant();

    $this->postJson('/api/sivarcart/cart/items', ['variant_id' => $variant->getKey()])
        ->assertCreated();

    $this->deleteJson('/api/sivarcart/cart')
        ->assertOk()
        ->assertJsonCount(0, 'data.items')
        ->assertJsonPath('data.total', 0);
});

it('gives the buyer a new cart when the old one already became an order', function () {
    $customer = buyer();

    $variant = Product::factory()->create()->defaultVariant();

    $converted = Cart::factory()->create([
        'customer_id' => $customer->getKey(),
        'converted_at' => now(),
    ]);

    $this->postJson('/api/sivarcart/cart/items', ['variant_id' => $variant->getKey()])
        ->assertCreated()
        ->assertJsonPath('data.id', fn (string $id): bool => $id !== $converted->uuid);

    $cart = app(CartService::class)->findOrCreate($customer->getKey());

    expect($cart->is($converted))->toBeFalse();
});
