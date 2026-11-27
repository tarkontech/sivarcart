<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Tarkontech\SivarCart\Http\Middleware\EnsureStorePermission;
use Tarkontech\SivarCart\Models\StoreRole;
use Tarkontech\SivarCart\Tests\Fixtures\PlainUser;
use Workbench\App\Models\User;

/**
 * A member of the staff of the store with a role that grants the permissions.
 *
 * @param  list<string>  $permissions
 */
function staffWithPermissions(array $permissions): User
{
    $user = User::factory()->create();

    $user->assignStoreRole(
        StoreRole::factory()->create(['permissions' => $permissions]),
    );

    return $user->fresh();
}

beforeEach(function () {
    Route::get('/permiso-protegido', fn (): string => 'ok')
        ->middleware(EnsureStorePermission::class.':orders.view');

    Route::get('/permiso-con-alias', fn (): string => 'ok')
        ->middleware('store.permission:orders.view');

    Route::get('/permiso-en-ruta/{permission}', fn (): string => 'ok')
        ->middleware(EnsureStorePermission::class);

    Route::get('/permiso-sin-declarar', fn (): string => 'ok')
        ->middleware(EnsureStorePermission::class);
});

it('lets a user with the permission through', function () {
    $this->actingAs(staffWithPermissions(['orders.view']));

    $this->get('/permiso-protegido')->assertOk();
});

it('refuses a user without the permission', function () {
    $this->actingAs(staffWithPermissions(['catalog.view']));

    $this->get('/permiso-protegido')->assertForbidden();
});

it('lets the owner of the store through with any permission', function () {
    $this->actingAs(staffWithPermissions(['*']));

    $this->get('/permiso-protegido')->assertOk();
    $this->get('/permiso-en-ruta/catalog.view')->assertOk();
});

it('refuses a request without a user', function () {
    $this->get('/permiso-protegido')->assertForbidden();
});

it('refuses a route that does not say what permission it needs', function () {
    $this->actingAs(staffWithPermissions(['orders.view']));

    $this->get('/permiso-sin-declarar')->assertForbidden();
});

it('reads the permission from the route', function () {
    $this->actingAs(staffWithPermissions(['orders.view']));

    $this->get('/permiso-en-ruta/orders.view')->assertOk();
    $this->get('/permiso-en-ruta/catalog.view')->assertForbidden();
});

it('refuses a user whose model does not carry the trait of the roles', function () {
    $this->actingAs(new PlainUser);

    $this->get('/permiso-protegido')->assertForbidden();
});

it('protects a route with the alias of the package', function () {
    $this->actingAs(staffWithPermissions(['orders.view']));

    $this->get('/permiso-con-alias')->assertOk();
});
