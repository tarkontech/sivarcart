<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Gate;
use SivarcartCore\SivarcartCore\Models\StoreRole;
use SivarcartCore\SivarcartCore\Tests\Fixtures\PlainUser;
use Workbench\App\Models\User;

it('answers the ability of the permissions with the roles of the user', function () {
    $user = User::factory()->create();

    $user->assignStoreRole(
        StoreRole::factory()->create(['permissions' => ['orders.view']]),
    );

    $this->actingAs($user->fresh());

    expect(Gate::allows('store-permission', 'orders.view'))->toBeTrue()
        ->and(Gate::allows('store-permission', 'orders.update'))->toBeFalse()
        ->and(Gate::allows('store-permission', 'catalog.view'))->toBeFalse();
});

it('denies the ability when nobody is authenticated', function () {
    expect(Gate::allows('store-permission', 'orders.view'))->toBeFalse();
});

it('denies the ability when the model of the user does not carry the trait', function () {
    $this->actingAs(new PlainUser);

    expect(Gate::allows('store-permission', 'orders.view'))->toBeFalse();
});

it('answers the ability for the user it is asked about', function () {
    $user = User::factory()->create();

    $user->assignStoreRole(
        StoreRole::factory()->create(['permissions' => ['*']]),
    );

    expect(Gate::forUser($user->fresh())->allows('store-permission', 'lo.que.sea'))->toBeTrue();
});
