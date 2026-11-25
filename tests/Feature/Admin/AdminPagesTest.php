<?php

declare(strict_types=1);

use Livewire\Livewire;
use SivarcartCore\SivarcartCore\Admin\AdminPanel;
use SivarcartCore\SivarcartCore\Enums\OrderStatus;
use SivarcartCore\SivarcartCore\Models\Customer;
use SivarcartCore\SivarcartCore\Models\Order;
use SivarcartCore\SivarcartCore\Models\Product;
use SivarcartCore\SivarcartCore\Models\StoreRole;
use SivarcartCore\SivarcartCore\Services\StoreService;
use Workbench\App\Models\User;

beforeEach(function () {
    app(AdminPanel::class)->registerLivewireComponents();
});

/**
 * Sign a member of the staff of the store in.
 */
function staffWith(array $permissions): User
{
    $user = User::factory()->create();

    $user->assignStoreRole(StoreRole::factory()->create(['permissions' => $permissions]));

    test()->actingAs($user);

    return $user;
}

it('shows the dashboard of the panel', function () {
    staffWith(['*']);

    Livewire::test('sivarcart.admin.page.dashboard')
        ->assertOk()
        ->assertSee('Orders today');
});

it('counts the orders of the day in the dashboard', function () {
    staffWith(['*']);

    Order::factory()->create(['placed_at' => now()]);

    Livewire::test('sivarcart.admin.page.dashboard')
        ->assertSee('Orders today');
});

it('shows the orders of the store', function () {
    staffWith(['*']);

    $order = Order::factory()->create(['order_number' => 'SIV-2026-00001']);

    Livewire::test('sivarcart.admin.page.orders')
        ->assertOk()
        ->assertSee('SIV-2026-00001');
});

it('searches the orders of the store', function () {
    staffWith(['*']);

    Order::factory()->create(['order_number' => 'SIV-2026-00001']);
    Order::factory()->create(['order_number' => 'OTRA-ORDEN-1']);

    Livewire::test('sivarcart.admin.page.orders')
        ->set('search', 'OTRA-ORDEN')
        ->assertSee('OTRA-ORDEN-1')
        ->assertDontSee('SIV-2026-00001');
});

it('shows the products of the catalog, the drafts included', function () {
    staffWith(['*']);

    Product::factory()->create(['name' => 'Un producto']);

    Livewire::test('sivarcart.admin.page.products')
        ->assertOk()
        ->assertSee('Un producto');
});

it('shows the buyers of the store', function () {
    staffWith(['*']);

    Customer::factory()->create(['email' => 'comprador@example.test']);

    Livewire::test('sivarcart.admin.page.customers')
        ->assertOk()
        ->assertSee('comprador@example.test');
});

it('shows the roles of the staff of the store', function () {
    staffWith(['*']);

    StoreRole::factory()->create(['name' => 'Administrador', 'slug' => 'administrador']);

    Livewire::test('sivarcart.admin.page.roles')
        ->assertOk()
        ->assertSee('Administrador')
        ->assertSee('administrador');
});

it('saves the settings of the store', function () {
    staffWith(['*']);

    Livewire::test('sivarcart.admin.page.settings')
        ->set('name', 'Mi Tienda')
        ->set('email', 'hola@mitienda.test')
        ->call('save')
        ->assertHasNoErrors();

    expect(app(StoreService::class)->current()?->name)
        ->toBe('Mi Tienda');
});

it('does not save the settings of the store without a name', function () {
    staffWith(['*']);

    Livewire::test('sivarcart.admin.page.settings')
        ->set('name', '')
        ->call('save')
        ->assertHasErrors('name');
});

it('shows one order of the store', function () {
    staffWith(['*']);

    $order = Order::factory()->create(['order_number' => 'SIV-2026-00002']);

    Livewire::test('sivarcart.orders.show', ['order' => $order->uuid])
        ->assertOk()
        ->assertSee('SIV-2026-00002');
});

it('moves an order through its lifecycle from the panel', function () {
    staffWith(['orders.view', 'orders.update']);

    $order = Order::factory()->create(['status' => OrderStatus::PENDING]);

    Livewire::test('sivarcart.orders.show', ['order' => $order->uuid])
        ->call('move', 'processing')
        ->assertHasNoErrors();

    expect($order->fresh()->status)->toBe(OrderStatus::PROCESSING);
});

it('does not move an order without the permission of the store', function () {
    staffWith(['orders.view']);

    $order = Order::factory()->create(['status' => OrderStatus::PENDING]);

    Livewire::test('sivarcart.orders.show', ['order' => $order->uuid])
        ->call('move', 'processing');

    expect($order->fresh()->status)->toBe(OrderStatus::PENDING);
});
