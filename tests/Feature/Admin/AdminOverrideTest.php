<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\File;
use SivarcartCore\SivarcartCore\Models\StoreRole;
use Workbench\App\Models\User;

it('lets the merchant override a view of the panel', function () {
    $path = base_path('resources/views/vendor/sivarcart-core/admin/pages/dashboard.blade.php');

    File::ensureDirectoryExists(dirname($path));
    File::put($path, '<p>PANEL SOBRESCRITO</p>');

    $user = User::factory()->create();
    $user->assignStoreRole(StoreRole::factory()->create(['permissions' => ['*']]));

    $this->actingAs($user);

    $this->get('/sivarcart/admin/dashboard')
        ->assertOk()
        ->assertSee('PANEL SOBRESCRITO')
        ->assertDontSee('siv-stats');
});

it('lets the merchant override a component of the panel', function () {
    $path = base_path('resources/views/vendor/sivarcart-core/admin/components/card.blade.php');

    File::ensureDirectoryExists(dirname($path));
    File::put($path, '<div>CAJA SOBRESCRITA</div>');

    $rendered = Blade::render('<x-sivarcart-core::card>El cuerpo</x-sivarcart-core::card>');

    expect($rendered)->toContain('CAJA SOBRESCRITA')
        ->and($rendered)->not->toContain('siv-card');
});

it('keeps the view of the package when the merchant does not override it', function () {
    $rendered = Blade::render('<x-sivarcart-core::card>El cuerpo</x-sivarcart-core::card>');

    expect($rendered)->toContain('siv-card')
        ->and($rendered)->toContain('El cuerpo');
});

it('lets the merchant override the layout of the panel', function () {
    $path = base_path('resources/views/vendor/sivarcart-core/admin/layout.blade.php');

    File::ensureDirectoryExists(dirname($path));
    File::put($path, '<html><body>LAYOUT SOBRESCRITO</body></html>');

    $user = User::factory()->create();
    $user->assignStoreRole(StoreRole::factory()->create(['permissions' => ['*']]));

    $this->actingAs($user);

    $this->get('/sivarcart/admin/dashboard')
        ->assertOk()
        ->assertSee('LAYOUT SOBRESCRITO')
        ->assertDontSee('siv-stats');
});

it('publishes the views of the panel for the merchant to edit them', function () {
    $this->artisan('vendor:publish', ['--tag' => 'sivarcart-core-views'])->assertSuccessful();

    expect(File::exists(base_path('resources/views/vendor/sivarcart-core/admin/layout.blade.php')))->toBeTrue()
        ->and(File::exists(base_path('resources/views/vendor/sivarcart-core/admin/components/card.blade.php')))->toBeTrue();
});
