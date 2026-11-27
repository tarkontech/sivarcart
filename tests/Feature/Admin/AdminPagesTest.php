<?php

declare(strict_types=1);

use Inertia\Testing\AssertableInertia as Assert;
use Tarkontech\SivarCart\Enums\OrderStatus;
use Tarkontech\SivarCart\Models\Customer;
use Tarkontech\SivarCart\Models\Order;
use Tarkontech\SivarCart\Models\Product;
use Tarkontech\SivarCart\Models\Store;
use Tarkontech\SivarCart\Models\StoreRole;
use Workbench\App\Models\User;

/**
 * Sign a member of the staff of the store in.
 *
 * @param  list<string>  $permissions
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

    $this->get('/sivarcart/admin/dashboard')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Dashboard')
            ->has('ordersToday')
            ->has('ordersAwaiting')
            ->has('ordersUnpaid')
            ->has('products')
            ->has('lowStock')
            ->has('widgets'),
        );
});

it('counts the orders of the day in the dashboard', function () {
    staffWith(['*']);

    Order::factory()->create(['placed_at' => now()]);

    $this->get('/sivarcart/admin/dashboard')
        ->assertInertia(fn (Assert $page) => $page->where('ordersToday', 1));
});

it('shows the orders of the store', function () {
    staffWith(['*']);

    $order = Order::factory()->create(['order_number' => 'SIV-2026-00001']);

    $this->get('/sivarcart/admin/orders')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Orders/Index')
            ->has('orders.data', 1)
            ->where('orders.data.0.number', 'SIV-2026-00001')
            ->where('orders.data.0.uuid', $order->uuid)
            ->has('statuses', 7),
        );
});

it('searches the orders of the store', function () {
    staffWith(['*']);

    Order::factory()->create(['order_number' => 'SIV-2026-00001']);
    Order::factory()->create(['order_number' => 'OTRA-ORDEN-1']);

    $this->get('/sivarcart/admin/orders?q=OTRA-ORDEN')
        ->assertInertia(fn (Assert $page) => $page
            ->has('orders.data', 1)
            ->where('orders.data.0.number', 'OTRA-ORDEN-1')
            ->where('filters.q', 'OTRA-ORDEN'),
        );
});

it('filters the orders of the store by their state', function () {
    staffWith(['*']);

    Order::factory()->create(['order_number' => 'SIV-2026-00001', 'status' => OrderStatus::PENDING]);
    Order::factory()->create(['order_number' => 'SIV-2026-00002', 'status' => OrderStatus::SHIPPED]);

    $this->get('/sivarcart/admin/orders?estado=shipped')
        ->assertInertia(fn (Assert $page) => $page
            ->has('orders.data', 1)
            ->where('orders.data.0.number', 'SIV-2026-00002')
            ->where('filters.status', 'shipped'),
        );
});

it('shows the state of an order with the tone it is read with', function () {
    staffWith(['*']);

    Order::factory()->create(['status' => OrderStatus::DELIVERED]);

    $this->get('/sivarcart/admin/orders')
        ->assertInertia(fn (Assert $page) => $page
            ->where('orders.data.0.status.tone', 'success')
            ->where('orders.data.0.status.label', 'Delivered'),
        );
});

it('shows the products of the catalog, the drafts included', function () {
    staffWith(['*']);

    Product::factory()->create(['name' => 'Un producto']);

    $this->get('/sivarcart/admin/products')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Products/Index')
            ->has('products.data', 1)
            ->where('products.data.0.name', 'Un producto'),
        );
});

it('searches the products of the catalog', function () {
    staffWith(['*']);

    Product::factory()->create(['name' => 'Un producto']);
    Product::factory()->create(['name' => 'Otro producto']);

    $this->get('/sivarcart/admin/products?q=Otro')
        ->assertInertia(fn (Assert $page) => $page
            ->has('products.data', 1)
            ->where('products.data.0.name', 'Otro producto'),
        );
});

it('shows the buyers of the store', function () {
    staffWith(['*']);

    Customer::factory()->create(['email' => 'comprador@example.test']);

    $this->get('/sivarcart/admin/customers')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Customers/Index')
            ->has('customers.data', 1)
            ->where('customers.data.0.email', 'comprador@example.test'),
        );
});

it('shows the roles of the staff of the store', function () {
    staffWith(['*']);

    StoreRole::factory()->create(['name' => 'Administrador', 'slug' => 'administrador']);

    $this->get('/sivarcart/admin/roles')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Roles/Index')
            ->has('roles')
            ->where('roles.0.name', 'Administrador')
            ->where('roles.0.slug', 'administrador'),
        );
});

it('shows the settings of the store', function () {
    staffWith(['*']);

    Store::factory()->create(['name' => 'Mi Tienda']);

    $this->get('/sivarcart/admin/settings')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Settings/Index')
            ->has('settings')
            ->where('settings.name', 'Mi Tienda'),
        );
});

it('shows one order of the store', function () {
    staffWith(['*']);

    $order = Order::factory()->create(['order_number' => 'SIV-2026-00002']);

    $this->get('/sivarcart/admin/orders/'.$order->uuid)
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Orders/Show')
            ->where('order.number', 'SIV-2026-00002')
            ->has('order.items')
            ->has('order.totals')
            ->has('order.buyer')
            ->has('order.timeline')
            ->where('canUpdate', true),
        );
});

it('does not show the moves of an order to the staff that cannot move it', function () {
    staffWith(['orders.view']);

    $order = Order::factory()->create(['status' => OrderStatus::PENDING]);

    $this->get('/sivarcart/admin/orders/'.$order->uuid)
        ->assertInertia(fn (Assert $page) => $page
            ->where('canUpdate', false)
            ->has('transitions', 0),
        );
});

it('shows the moves an order can be taken through', function () {
    staffWith(['orders.view', 'orders.update']);

    $order = Order::factory()->create(['status' => OrderStatus::PENDING]);

    $this->get('/sivarcart/admin/orders/'.$order->uuid)
        ->assertInertia(fn (Assert $page) => $page
            ->has('transitions', 3)
            ->where('transitions.0.value', 'processing'),
        );
});

it('answers 404 for an order that does not exist', function () {
    staffWith(['*']);

    $this->get('/sivarcart/admin/orders/un-pedido-que-no-existe')->assertNotFound();
});
