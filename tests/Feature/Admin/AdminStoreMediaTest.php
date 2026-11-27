<?php

declare(strict_types=1);

use Illuminate\Http\UploadedFile;
use Tarkontech\SivarCart\Models\Store;
use Tarkontech\SivarCart\Models\StoreRole;
use Workbench\App\Models\User;

/**
 * Sign a member of the staff of the store in.
 *
 * @param  list<string>  $permissions
 */
function staffOfStore(array $permissions = ['*']): User
{
    $user = User::factory()->create();

    $user->assignStoreRole(StoreRole::factory()->create(['permissions' => $permissions]));

    test()->actingAs($user);

    return $user;
}

/**
 * A file of image that the store accepts.
 */
function imageOfTheStore(string $name = 'logo.png'): UploadedFile
{
    return UploadedFile::fake()->image($name, 200, 200);
}

it('shows the logo and the favicon of the store in the settings', function () {
    staffOfStore();

    Store::factory()->create(['name' => 'Mi Tienda']);

    $this->get('/sivarcart/admin/settings')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Admin/Settings/Index')
            ->where('settings.name', 'Mi Tienda')
            ->where('logo', null)
            ->where('favicon', null)
            ->where('canUpdate', true),
        );
});

it('writes the logo of the store', function () {
    staffOfStore();

    $store = Store::factory()->create(['name' => 'Mi Tienda']);

    $this->post('/sivarcart/admin/settings/logo', [
        'logo' => imageOfTheStore(),
    ])->assertRedirect();

    expect($store->fresh()->logo_path)->not->toBeNull();
});

it('writes the favicon of the store', function () {
    staffOfStore();

    $store = Store::factory()->create(['name' => 'Mi Tienda']);

    $this->post('/sivarcart/admin/settings/favicon', [
        'favicon' => imageOfTheStore('favicon.png'),
    ])->assertRedirect();

    expect($store->fresh()->favicon_path)->not->toBeNull();
});

it('does not write a file that the store does not accept', function () {
    staffOfStore();

    $store = Store::factory()->create(['name' => 'Mi Tienda']);

    $this->post('/sivarcart/admin/settings/logo', [
        'logo' => UploadedFile::fake()->create('documento.pdf', 10, 'application/pdf'),
    ])->assertRedirect();

    expect($store->fresh()->logo_path)->toBeNull();
});

it('does not write the images of the store to the staff that cannot', function () {
    staffOfStore(['settings.view']);

    $store = Store::factory()->create(['name' => 'Mi Tienda']);

    $this->post('/sivarcart/admin/settings/logo', [
        'logo' => imageOfTheStore(),
    ])->assertForbidden();

    expect($store->fresh()->logo_path)->toBeNull();
});
