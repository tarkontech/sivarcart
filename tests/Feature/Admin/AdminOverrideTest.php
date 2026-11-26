<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use SivarcartCore\SivarcartCore\Models\StoreRole;
use Workbench\App\Models\User;

it('writes the theme of the package in the page', function () {
    $user = User::factory()->create();

    $user->assignStoreRole(StoreRole::factory()->create(['permissions' => ['*']]));

    $this->actingAs($user);

    $response = $this->get('/sivarcart/admin/dashboard');

    expect($response->getContent())
        ->toContain('--primary: #1d4ed8')
        ->toContain('--destructive: #b91c1c')
        ->toContain('--success: #15803d')
        ->toContain('--warning: #b45309');
});

it('lets the merchant change the theme of the package', function () {
    config(['sivarcart-core.admin.theme.primary' => '#ff0000']);

    $user = User::factory()->create();

    $user->assignStoreRole(StoreRole::factory()->create(['permissions' => ['*']]));

    $this->actingAs($user);

    expect($this->get('/sivarcart/admin/dashboard')->getContent())
        ->toContain('--primary: #ff0000');
});

it('publishes the source of the panel for the merchant to edit it', function () {
    $this->artisan('vendor:publish', ['--tag' => 'sivarcart-core-admin-source'])->assertSuccessful();

    expect(File::exists(resource_path('js/sivarcart/admin.tsx')))->toBeTrue()
        ->and(File::exists(resource_path('js/sivarcart/pages/Admin/Dashboard.tsx')))->toBeTrue()
        ->and(File::exists(resource_path('js/sivarcart/components/ui/button.tsx')))->toBeTrue();
});

it('does not overwrite the source of the panel the merchant already edited', function () {
    $this->artisan('vendor:publish', ['--tag' => 'sivarcart-core-admin-source'])->assertSuccessful();

    $path = resource_path('js/sivarcart/admin.tsx');

    File::put($path, '// MI ADMIN');

    $this->artisan('vendor:publish', ['--tag' => 'sivarcart-core-admin-source'])->assertSuccessful();

    expect(File::get($path))->toBe('// MI ADMIN');
});

it('publishes the root view of the panel with a tag of its own', function () {
    $this->artisan('vendor:publish', ['--tag' => 'sivarcart-core-panel-view'])->assertSuccessful();

    expect(File::exists(resource_path('views/vendor/sivarcart-core-panel/app.blade.php')))->toBeTrue();
});

it('does not let the views the merchant publishes reach the root view of the panel', function () {
    $path = resource_path('views/vendor/sivarcart-core/app.blade.php');

    File::ensureDirectoryExists(dirname($path));
    File::put($path, '<html><body>UNA VISTA QUE NO ES</body></html>');

    $user = User::factory()->create();

    $user->assignStoreRole(StoreRole::factory()->create(['permissions' => ['*']]));

    $this->actingAs($user);

    $response = $this->get('/sivarcart/admin/dashboard');

    expect($response->getContent())
        ->toContain('id="sivarcart-admin"')
        ->not->toContain('UNA VISTA QUE NO ES');
});

it('does not publish the source of the panel with the views of the package', function () {
    $this->artisan('vendor:publish', ['--tag' => 'sivarcart-core-views'])->assertSuccessful();

    expect(File::exists(resource_path('js/sivarcart/admin.tsx')))->toBeFalse();
});

it('publishes a registry of shadcn with the source of the panel', function () {
    $registry = json_decode((string) File::get(dirname(__DIR__, 3).'/registry.json'), true);

    expect($registry)->toBeArray()
        ->and($registry['$schema'])->toBe('https://ui.shadcn.com/schema/registry.json')
        ->and($registry['name'])->toBe('sivarcart')
        ->and($registry['items'])->not->toBeEmpty();
});

it('gives every item of the registry a name, a type and its files', function () {
    $registry = json_decode((string) File::get(dirname(__DIR__, 3).'/registry.json'), true);

    $items = $registry['items'];

    expect($items)->not->toBeEmpty();

    foreach ($items as $item) {
        expect($item)->toHaveKeys(['name', 'type', 'files'])
            ->and($item['files'])->not->toBeEmpty();
    }
});

it('ships every file of the registry in the package', function () {
    $registry = json_decode((string) File::get(dirname(__DIR__, 3).'/registry.json'), true);

    $missing = [];

    foreach ($registry['items'] as $item) {
        foreach ($item['files'] as $file) {
            if (! File::exists(dirname(__DIR__, 3).'/'.$file['path'])) {
                $missing[] = $file['path'];
            }
        }
    }

    expect($missing)->toBe([]);
});

it('publishes the whole panel as a base of shadcn', function () {
    $registry = json_decode((string) File::get(dirname(__DIR__, 3).'/registry.json'), true);

    $base = null;

    foreach ($registry['items'] as $item) {
        if ($item['name'] === 'admin') {
            $base = $item;
        }
    }

    expect($base)->not->toBeNull()
        ->and($base['type'])->toBe('registry:base')
        ->and($base['config']['tailwind']['css'])->toBe('resources/css/sivarcart.css')
        ->and($base['config']['aliases']['ui'])->toBe('@/components/sivarcart/ui');
});
