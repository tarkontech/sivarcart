<?php

declare(strict_types=1);

use Tarkontech\SivarCart\Database\Seeders\StoreRoleSeeder;
use Tarkontech\SivarCart\Enums\OrderStatus;
use Tarkontech\SivarCart\Models\Order;
use Tarkontech\SivarCart\Models\StoreRole;
use Tarkontech\SivarCart\Services\StoreService;
use Workbench\App\Models\User;

/**
 * Sign a member of the staff of the store in.
 *
 * @param  list<string>  $permissions
 */
function staffWithPermission(array $permissions): User
{
    $user = User::factory()->create();

    $user->assignStoreRole(StoreRole::factory()->create(['permissions' => $permissions]));

    test()->actingAs($user);

    return $user;
}

it('moves an order through its lifecycle from the panel', function () {
    staffWithPermission(['orders.view', 'orders.update']);

    $order = Order::factory()->create(['status' => OrderStatus::PENDING]);

    $this->post('/sivarcart/admin/orders/'.$order->uuid.'/status', [
        'status' => 'processing',
    ])->assertStatus(303);

    expect($order->fresh()->status)->toBe(OrderStatus::PROCESSING);
});

it('does not move an order without the permission of the store', function () {
    staffWithPermission(['orders.view']);

    $order = Order::factory()->create(['status' => OrderStatus::PENDING]);

    $this->post('/sivarcart/admin/orders/'.$order->uuid.'/status', [
        'status' => 'processing',
    ])->assertForbidden();

    expect($order->fresh()->status)->toBe(OrderStatus::PENDING);
});

it('does not move an order to a state it cannot be taken to', function () {
    staffWithPermission(['orders.view', 'orders.update']);

    $order = Order::factory()->create(['status' => OrderStatus::DELIVERED]);

    $this->post('/sivarcart/admin/orders/'.$order->uuid.'/status', [
        'status' => 'processing',
    ])->assertStatus(303);

    expect($order->fresh()->status)->toBe(OrderStatus::DELIVERED);
});

it('does not move an order to a state the panel does not offer', function () {
    staffWithPermission(['orders.view', 'orders.update']);

    $order = Order::factory()->create(['status' => OrderStatus::PENDING]);

    $this->post('/sivarcart/admin/orders/'.$order->uuid.'/status', [
        'status' => 'refunded',
    ])->assertStatus(303);

    expect($order->fresh()->status)->toBe(OrderStatus::PENDING);
});

it('saves the settings of the store', function () {
    staffWithPermission(['settings.view', 'settings.update']);

    $this->put('/sivarcart/admin/settings', [
        'name' => 'Mi Tienda',
        'legal_name' => 'Mi Tienda S.A.',
        'tax_id' => '0614-123456-101-2',
        'email' => 'hola@mitienda.test',
        'phone' => '22123456',
    ])->assertStatus(303);

    $store = app(StoreService::class)->current();

    expect($store?->name)->toBe('Mi Tienda')
        ->and($store?->legal_name)->toBe('Mi Tienda S.A.')
        ->and($store?->email)->toBe('hola@mitienda.test');
});

it('does not save the settings of the store without a name', function () {
    staffWithPermission(['settings.view', 'settings.update']);

    $before = app(StoreService::class)->current()?->name;

    $this->put('/sivarcart/admin/settings', [
        'name' => '',
    ])->assertSessionHasErrors('name');

    expect(app(StoreService::class)->current()?->name)->toBe($before);
});

it('does not save the settings of the store without the permission of the store', function () {
    staffWithPermission(['settings.view']);

    $before = app(StoreService::class)->current()?->name;

    $this->put('/sivarcart/admin/settings', [
        'name' => 'Otro nombre',
    ])->assertForbidden();

    expect(app(StoreService::class)->current()?->name)->toBe($before);
});

it('tells the staff of the store that the settings were saved', function () {
    staffWithPermission(['settings.view', 'settings.update']);

    $this->put('/sivarcart/admin/settings', ['name' => 'Mi Tienda'])->assertStatus(303);

    $this->get('/sivarcart/admin/settings')
        ->assertInertia(fn ($page) => $page
            ->component('Admin/Settings/Index')
            ->hasFlash('notice', __('sivarcart::admin.settings.saved')),
        );
});

it('tells the staff of the store that the order moved', function () {
    staffWithPermission(['orders.view', 'orders.update']);

    $order = Order::factory()->create(['status' => OrderStatus::PENDING]);

    $this->post('/sivarcart/admin/orders/'.$order->uuid.'/status', [
        'status' => 'processing',
    ])->assertStatus(303);

    $this->get('/sivarcart/admin/orders/'.$order->uuid)
        ->assertInertia(fn ($page) => $page
            ->component('Admin/Orders/Show')
            ->hasFlash('notice', __('sivarcart::admin.orders.moved', [
                'status' => OrderStatus::PROCESSING->label(),
            ])),
        );
});

it('gives the permission of writing the settings to the manager of the store', function () {
    $this->artisan('db:seed', ['--class' => StoreRoleSeeder::class]);

    $manager = StoreRole::query()->where('slug', 'manager')->first();

    expect($manager)->not->toBeNull()
        ->and($manager?->hasPermission('settings.update'))->toBeTrue()
        ->and($manager?->hasPermission('settings.view'))->toBeTrue();
});

it('does not give the permission of writing the settings to the staff of the store', function () {
    $this->artisan('db:seed', ['--class' => StoreRoleSeeder::class]);

    $staff = StoreRole::query()->where('slug', 'staff')->first();

    expect($staff)->not->toBeNull()
        ->and($staff?->hasPermission('settings.update'))->toBeFalse();
});
