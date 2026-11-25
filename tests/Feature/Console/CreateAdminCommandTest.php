<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Hash;
use SivarcartCore\SivarcartCore\Models\StoreRole;
use Workbench\App\Models\User;

it('creates a member of the staff of the store', function () {
    StoreRole::factory()->create(['slug' => 'owner', 'permissions' => ['*']]);

    $this->artisan('sivarcart-core:create-admin', [
        '--name' => 'Dueño',
        '--name' => 'Dueño',
        '--email' => 'dueno@tienda.test',
        '--password' => 'una-clave-larga',
    ])->assertSuccessful();

    $user = User::query()->where('email', 'dueno@tienda.test')->firstOrFail();

    expect($user->name)->toBe('Dueño')
        ->and($user->hasStoreRole('owner'))->toBeTrue()
        ->and($user->hasStorePermission('orders.view'))->toBeTrue();
});

it('hashes the password of the member of the staff', function () {
    StoreRole::factory()->create(['slug' => 'owner']);

    $this->artisan('sivarcart-core:create-admin', [
        '--name' => 'Dueño',
        '--email' => 'dueno@tienda.test',
        '--password' => 'una-clave-larga',
    ])->assertSuccessful();

    $user = User::query()->where('email', 'dueno@tienda.test')->firstOrFail();

    expect($user->password)->not->toBe('una-clave-larga')
        ->and(Hash::check('una-clave-larga', $user->password))->toBeTrue();
});

it('gives the member of the staff the role it was told', function () {
    StoreRole::factory()->create(['slug' => 'staff', 'permissions' => ['orders.view']]);

    $this->artisan('sivarcart-core:create-admin', [
        '--name' => 'Empleado',
        '--email' => 'empleado@tienda.test',
        '--password' => 'una-clave-larga',
        '--role' => 'staff',
    ])->assertSuccessful();

    $user = User::query()->where('email', 'empleado@tienda.test')->firstOrFail();

    expect($user->hasStoreRole('staff'))->toBeTrue()
        ->and($user->hasStorePermission('orders.view'))->toBeTrue()
        ->and($user->hasStorePermission('orders.update'))->toBeFalse();
});

it('does not create a member of the staff with an email that is in use', function () {
    StoreRole::factory()->create(['slug' => 'owner']);

    User::factory()->create(['email' => 'dueno@tienda.test']);

    $this->artisan('sivarcart-core:create-admin', [
        '--name' => 'Dueño',
        '--email' => 'dueno@tienda.test',
        '--password' => 'una-clave-larga',
    ])->assertFailed();

    expect(User::query()->where('email', 'dueno@tienda.test')->count())->toBe(1);
});

it('does not create a member of the staff with an email that is not one', function () {
    StoreRole::factory()->create(['slug' => 'owner']);

    $this->artisan('sivarcart-core:create-admin', [
        '--name' => 'Dueño',
        '--email' => 'esto-no-es-un-correo',
        '--password' => 'una-clave-larga',
    ])->assertFailed();

    expect(User::query()->count())->toBe(0);
});

it('does not create a member of the staff with a role that does not exist', function () {
    StoreRole::factory()->create(['slug' => 'owner']);

    $this->artisan('sivarcart-core:create-admin', [
        '--name' => 'Dueño',
        '--email' => 'dueno@tienda.test',
        '--password' => 'una-clave-larga',
        '--role' => 'jefe',
    ])->assertFailed();

    expect(User::query()->count())->toBe(0);
});

it('does not create a member of the staff with a short password', function () {
    StoreRole::factory()->create(['slug' => 'owner']);

    $this->artisan('sivarcart-core:create-admin', [
        '--name' => 'Dueño',
        '--email' => 'dueno@tienda.test',
        '--password' => 'corta',
    ])->assertFailed();

    expect(User::query()->count())->toBe(0);
});
