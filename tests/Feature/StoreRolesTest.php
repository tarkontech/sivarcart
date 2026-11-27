<?php

declare(strict_types=1);

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tarkontech\SivarCart\Database\Seeders\StoreRoleSeeder;
use Tarkontech\SivarCart\Enums\OrderStatus;
use Tarkontech\SivarCart\Models\InventoryMovement;
use Tarkontech\SivarCart\Models\Order;
use Tarkontech\SivarCart\Models\StoreRole;
use Tarkontech\SivarCart\Support\Auth;
use Tarkontech\SivarCart\Tests\Fixtures\UuidUser;
use Workbench\App\Models\User;

it('takes the user of the application from the provider of the auth configuration', function () {
    expect(Auth::model())->toBe(User::class);
    expect(Auth::guard())->toBe('web');
});

it('lets the application name another user model and another guard', function () {
    config([
        'sivarcart.auth.model' => User::class,
        'sivarcart.auth.guard' => 'admin',
    ]);

    expect(Auth::model())->toBe(User::class);
    expect(Auth::guard())->toBe('admin');
});

it('does not own a table of staff', function () {
    expect(Schema::hasTable('sivarcart_store_users'))->toBeFalse();
});

it('relates the roles of the store to the users table of the application', function () {
    $foreignKeys = collect(Schema::getForeignKeys('sivarcart_store_role_user'));

    expect($foreignKeys)->not->toBeEmpty();

    $foreignKey = $foreignKeys->firstWhere('columns', ['user_id']);

    expect($foreignKey)->not->toBeNull();
    expect($foreignKey['foreign_table'])->toBe('users');
    expect($foreignKey['foreign_columns'])->toBe(['id']);
});

it('relates the ledger of the stock to the users table of the application', function () {
    $foreignKeys = collect(Schema::getForeignKeys('sivarcart_inventory_movements'));

    $foreignKey = $foreignKeys->firstWhere('columns', ['user_id']);

    expect($foreignKey)->not->toBeNull();
    expect($foreignKey['foreign_table'])->toBe('users');
});

it('derives the column of the staff from the key of the user model', function () {
    $blueprint = new Blueprint(DB::connection(), 'sivarcart_store_role_user');

    $integer = $blueprint->foreignIdFor(Auth::newModel(), 'user_id');

    expect($integer->name)->toBe('user_id');
    expect($integer->type)->toBe('bigInteger');
    expect($integer->table)->toBe('users');
    expect($integer->referencesModelColumn)->toBe('id');

    $uuid = $blueprint->foreignIdFor(new UuidUser, 'user_id');

    expect($uuid->name)->toBe('user_id');
    expect($uuid->type)->toBe('uuid');
    expect($uuid->table)->toBe('users');
    expect($uuid->referencesModelColumn)->toBe('id');
});

it('gives the staff of the store the roles of the application', function () {
    $user = User::factory()->create();
    $role = StoreRole::factory()->create(['permissions' => ['orders.view']]);

    $user->assignStoreRole($role);

    expect($user->fresh()->storeRoles)->toHaveCount(1);
    expect($user->fresh()->storeRoles->first()->permissions)->toBe(['orders.view']);
    expect($role->fresh()->users)->toHaveCount(1);
    expect($role->fresh()->users->first()->is($user))->toBeTrue();
});

it('tells whether a user holds a role of the store', function () {
    $user = User::factory()->create();
    StoreRole::factory()->create(['slug' => 'manager']);

    expect($user->hasStoreRole('manager'))->toBeFalse();

    $user->assignStoreRole('manager');

    expect($user->fresh()->hasStoreRole('manager'))->toBeTrue();
    expect($user->fresh()->hasStoreRole('owner'))->toBeFalse();
});

it('grants the permissions of the roles of the user', function () {
    $user = User::factory()->create();

    $user->assignStoreRole(
        StoreRole::factory()->create(['permissions' => ['orders.*']]),
        StoreRole::factory()->create(['permissions' => ['settings.view']]),
    );

    expect($user->fresh()->hasStorePermission('orders.view'))->toBeTrue();
    expect($user->fresh()->hasStorePermission('orders.update'))->toBeTrue();
    expect($user->fresh()->hasStorePermission('settings.view'))->toBeTrue();
    expect($user->fresh()->hasStorePermission('settings.update'))->toBeFalse();
    expect($user->fresh()->hasStorePermission('catalog.view'))->toBeFalse();
});

it('grants every permission to the owner of the store', function () {
    $user = User::factory()->create();

    $user->assignStoreRole(StoreRole::factory()->create(['permissions' => ['*']]));

    expect($user->fresh()->hasStorePermission('anything.at.all'))->toBeTrue();
});

it('takes a role away from the user', function () {
    $user = User::factory()->create();
    $role = StoreRole::factory()->create(['slug' => 'manager']);

    $user->assignStoreRole($role);

    expect($user->fresh()->storeRoles)->toHaveCount(1);

    $user->revokeStoreRole('manager');

    expect($user->fresh()->storeRoles)->toHaveCount(0);
});

it('seeds the vocabulary of the permissions of the store', function () {
    $this->seed(StoreRoleSeeder::class);

    expect(StoreRole::query()->where('slug', 'owner')->firstOrFail()->permissions)->toBe(['*'])
        ->and(StoreRole::query()->where('slug', 'manager')->firstOrFail()->permissions)->toBe([
            'catalog.*',
            'orders.*',
            'customers.*',
            'payments.*',
            'shipping.*',
            'settings.view',
            'settings.update',
        ])
        ->and(StoreRole::query()->where('slug', 'staff')->firstOrFail()->permissions)->toBe([
            'catalog.view',
            'orders.view',
            'orders.update',
            'customers.view',
        ]);
});

it('does not seed a permission that protects nothing', function () {
    $this->seed(StoreRoleSeeder::class);

    $groups = StoreRole::query()
        ->get()
        ->flatMap(fn (StoreRole $role): array => $role->permissions ?? [])
        ->filter(fn (mixed $permission): bool => is_string($permission) && $permission !== '*')
        ->map(fn (string $permission): string => explode('.', $permission)[0])
        ->unique()
        ->values()
        ->all();

    expect($groups)->toBe(['catalog', 'orders', 'customers', 'payments', 'shipping', 'settings']);
});

it('reads the user that is authenticated right now', function () {
    $user = User::factory()->create();

    expect(Auth::user())->toBeNull();

    $this->actingAs($user);

    expect(Auth::user())->not->toBeNull();
    expect(Auth::user()->is($user))->toBeTrue();
});

it('records the user of the application in the history of an order', function () {
    $order = Order::factory()->create();
    $user = User::factory()->create();

    $order->transitionTo(OrderStatus::CANCELLED, $user);

    $history = $order->statusHistories()->first();

    expect($history->user)->not->toBeNull();
    expect($history->user->is($user))->toBeTrue();
});

it('records the user of the application in the ledger of the stock', function () {
    $user = User::factory()->create();

    $movement = InventoryMovement::factory()->create(['user_id' => $user->getKey()]);

    expect($movement->user)->not->toBeNull();
    expect($movement->user->is($user))->toBeTrue();
});
