<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use SivarcartCore\SivarcartCore\Admin\AdminPanel;
use SivarcartCore\SivarcartCore\Admin\AdminWidgetProps;
use SivarcartCore\SivarcartCore\Models\StoreRole;
use Workbench\App\Models\User;

it('registers the pages of the package', function () {
    $panel = app(AdminPanel::class);

    expect($panel->find('dashboard'))->not->toBeNull()
        ->and($panel->find('orders'))->not->toBeNull()
        ->and($panel->find('products'))->not->toBeNull()
        ->and($panel->find('customers'))->not->toBeNull()
        ->and($panel->find('settings'))->not->toBeNull()
        ->and($panel->find('roles'))->not->toBeNull();
});

it('gives every page of the package the name of its component', function () {
    $panel = app(AdminPanel::class);

    expect($panel->find('dashboard')?->component)->toBe('Admin/Dashboard')
        ->and($panel->find('orders')?->component)->toBe('Admin/Orders/Index')
        ->and($panel->find('products')?->component)->toBe('Admin/Products/Index')
        ->and($panel->find('customers')?->component)->toBe('Admin/Customers/Index')
        ->and($panel->find('settings')?->component)->toBe('Admin/Settings/Index')
        ->and($panel->find('roles')?->component)->toBe('Admin/Roles/Index');
});

it('gives every page of the package a permission', function () {
    $panel = app(AdminPanel::class);

    expect($panel->find('orders')?->permission)->toBe('orders.view')
        ->and($panel->find('products')?->permission)->toBe('catalog.view')
        ->and($panel->find('customers')?->permission)->toBe('customers.view')
        ->and($panel->find('settings')?->permission)->toBe('settings.view')
        ->and($panel->find('roles')?->permission)->toBe('roles.view');
});

it('lets the application register a page of its own', function () {
    $panel = app(AdminPanel::class);

    $page = $panel->page(
        key: 'quick-view',
        label: 'Vista rápida',
        permission: 'orders.view',
        component: 'Admin/QuickView',
    );

    expect($panel->find('quick-view'))->not->toBeNull()
        ->and($page->component)->toBe('Admin/QuickView')
        ->and($page->belongsToPackage())->toBeTrue();
});

it('lets the application register a page with its own route', function () {
    Route::get('mi-ruta', fn (): string => 'ok')->name('mi-ruta');

    $panel = app(AdminPanel::class);

    $page = $panel->page(
        key: 'quick-view',
        label: 'Vista rápida',
        route: 'mi-ruta',
    );

    expect($page->belongsToPackage())->toBeFalse()
        ->and($page->url())->toBe(route('mi-ruta'));
});

it('puts the URL of a page of the package under the prefix of the panel', function () {
    $page = app(AdminPanel::class)->find('orders');

    expect($page?->url())->toBe(url('sivarcart/admin/orders'));
});

it('keeps the order of the navigation', function () {
    $panel = app(AdminPanel::class);

    $panel->page(key: 'zzz', label: 'Al final', order: 900);
    $panel->page(key: 'aaa', label: 'Al principio', order: 5);

    $keys = array_map(fn ($page): string => $page->key, $panel->pages());

    expect(array_search('aaa', $keys, true))->toBeLessThan(array_search('zzz', $keys, true));
});

it('only shows the pages the staff of the store is allowed to see', function () {
    $user = User::factory()->create();

    $user->assignStoreRole(StoreRole::factory()->create(['permissions' => ['orders.view']]));

    $this->actingAs($user);

    $keys = array_map(fn ($page): string => $page->key, app(AdminPanel::class)->visiblePages());

    expect($keys)->toContain('orders')
        ->and($keys)->not->toContain('products')
        ->and($keys)->not->toContain('settings');
});

it('lets the owner of the store see every page', function () {
    $user = User::factory()->create();

    $user->assignStoreRole(StoreRole::factory()->create(['permissions' => ['*']]));

    $this->actingAs($user);

    $keys = array_map(fn ($page): string => $page->key, app(AdminPanel::class)->visiblePages());

    expect($keys)->toContain('orders')
        ->and($keys)->toContain('products')
        ->and($keys)->toContain('settings')
        ->and($keys)->toContain('roles');
});

it('registers a widget of the dashboard', function () {
    $panel = app(AdminPanel::class);

    $widget = $panel->widget('mi-widget', 'Admin/Widgets/MiWidget');

    expect($panel->visibleWidgets())->toHaveCount(1)
        ->and($widget->component)->toBe('Admin/Widgets/MiWidget')
        ->and($widget->props)->toBeNull();
});

it('gives the data of a widget to the resolver of the widget', function () {
    $resolver = new class implements AdminWidgetProps
    {
        /**
         * @return array<string, mixed>
         */
        public function props(Request $request): array
        {
            return ['total' => 7];
        }
    };

    $widget = app(AdminPanel::class)->widget('mi-widget', 'Admin/Widgets/MiWidget', $resolver);

    expect($widget->props)->not->toBeNull()
        ->and($widget->props?->props(request()))->toBe(['total' => 7]);
});
