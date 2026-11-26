<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia as Assert;
use SivarcartCore\SivarcartCore\Admin\AdminBundle;
use SivarcartCore\SivarcartCore\Models\StoreRole;
use SivarcartCore\SivarcartCore\Tests\Fixtures\PlainUser;
use Workbench\App\Models\User;

it('registers the routes of the panel', function () {
    expect(Route::has('sivarcart-core.admin.index'))->toBeTrue()
        ->and(Route::has('sivarcart-core.admin.page'))->toBeTrue()
        ->and(Route::has('sivarcart-core.admin.orders.show'))->toBeTrue()
        ->and(Route::has('sivarcart-core.admin.orders.status'))->toBeTrue()
        ->and(Route::has('sivarcart-core.admin.settings.update'))->toBeTrue()
        ->and(Route::has('sivarcart-core.admin.login'))->toBeTrue()
        ->and(Route::has('sivarcart-core.admin.setup'))->toBeTrue()
        ->and(Route::has('sivarcart-core.admin.assets.css'))->toBeTrue()
        ->and(Route::has('sivarcart-core.admin.assets.js'))->toBeTrue()
        ->and(Route::has('sivarcart-core.admin.assets.chunk'))->toBeTrue();
});

it('mounts the panel under the configured prefix', function () {
    $route = Route::getRoutes()->getByName('sivarcart-core.admin.page');

    expect($route)->not->toBeNull()
        ->and($route->uri())->toBe('sivarcart/admin/{page}')
        ->and($route->methods())->toContain('GET');
});

it('gives the panel the middleware of the package', function () {
    $middleware = Route::getRoutes()
        ->getByName('sivarcart-core.admin.page')
        ->gatherMiddleware();

    expect($middleware)->toContain('sivarcart.admin')
        ->and($middleware)->toContain('web');
});

it('protects the orders of the panel with the permission of the store', function () {
    $middleware = Route::getRoutes()
        ->getByName('sivarcart-core.admin.orders.show')
        ->gatherMiddleware();

    expect($middleware)->toContain('store.permission:orders.view');
});

it('protects the writing of the panel with the permission of the store', function () {
    $status = Route::getRoutes()
        ->getByName('sivarcart-core.admin.orders.status')
        ->gatherMiddleware();

    $settings = Route::getRoutes()
        ->getByName('sivarcart-core.admin.settings.update')
        ->gatherMiddleware();

    expect($status)->toContain('store.permission:orders.update')
        ->and($settings)->toContain('store.permission:settings.update');
});

it('sends the staff that are not signed in to the sign in screen', function () {
    $this->get('/sivarcart/admin/dashboard')
        ->assertRedirect(route('sivarcart-core.admin.login'));
});

it('shows the sign in screen of the panel', function () {
    $this->get('/sivarcart/admin/login')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Admin/Login')->has('email'));
});

it('signs the staff of the store in with the guard of the application', function () {
    $user = User::factory()->create(['password' => 'una-clave-larga']);

    $this->post('/sivarcart/admin/login', [
        'email' => $user->email,
        'password' => 'una-clave-larga',
    ])->assertRedirect(route('sivarcart-core.admin.index'));

    expect(auth()->guard('web')->check())->toBeTrue();
});

it('does not sign the staff in with the wrong password', function () {
    $user = User::factory()->create(['password' => 'una-clave-larga']);

    $this->post('/sivarcart/admin/login', [
        'email' => $user->email,
        'password' => 'otra-clave',
    ])->assertSessionHasErrors('email');

    expect(auth()->guard('web')->check())->toBeFalse();
});

it('sends a user whose model does not carry the trait of the roles to the setup screen', function () {
    $this->actingAs(new PlainUser);

    $this->get('/sivarcart/admin/dashboard')
        ->assertRedirect(route('sivarcart-core.admin.setup'));
});

it('shows the setup screen of the panel', function () {
    $this->get('/sivarcart/admin/setup')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Setup')
            ->where('model', User::class)
            ->has('signedIn'),
        );
});

it('refuses a page the staff of the store is not allowed to see', function () {
    $user = User::factory()->create();

    $user->assignStoreRole(StoreRole::factory()->create(['permissions' => ['orders.view']]));

    $this->actingAs($user);

    $this->get('/sivarcart/admin/products')->assertForbidden();
});

it('answers 404 for a page that does not exist', function () {
    $user = User::factory()->create();

    $user->assignStoreRole(StoreRole::factory()->create(['permissions' => ['*']]));

    $this->actingAs($user);

    $this->get('/sivarcart/admin/una-pagina-que-no-existe')->assertNotFound();
});

it('shows the navigation of the panel in the language of the application', function () {
    $user = User::factory()->create();

    $user->assignStoreRole(StoreRole::factory()->create(['permissions' => ['*']]));

    $this->actingAs($user);

    $this->get('/sivarcart/admin/dashboard')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Dashboard')
            ->has('nav', 11)
            ->where('nav.1.label', 'Orders'),
        );
});

it('mounts the panel in its own element', function () {
    $user = User::factory()->create();

    $user->assignStoreRole(StoreRole::factory()->create(['permissions' => ['*']]));

    $this->actingAs($user);

    $response = $this->get('/sivarcart/admin/dashboard');

    expect($response->getContent())
        ->toContain('id="sivarcart-admin"')
        ->toContain('/sivarcart/admin/assets/'.AdminBundle::version().'/admin.css')
        ->toContain('/sivarcart/admin/assets/'.AdminBundle::version().'/admin.js');
});
