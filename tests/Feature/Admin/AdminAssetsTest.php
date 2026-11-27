<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use SivarcartCore\SivarcartCore\Admin\AdminBundle;
use SivarcartCore\SivarcartCore\Models\StoreRole;
use Workbench\App\Models\User;

it('serves the stylesheet of the panel', function () {
    $response = $this->get('/sivarcart/admin/assets/'.AdminBundle::version().'/admin.css');

    $response->assertOk();

    expect($response->headers->get('content-type'))->toContain('text/css');
});

it('serves the script of the panel', function () {
    $response = $this->get('/sivarcart/admin/assets/'.AdminBundle::version().'/admin.js');

    $response->assertOk();

    expect($response->headers->get('content-type'))->toContain('text/javascript');
});

it('serves the stylesheet and the script of the panel with the same version', function () {
    $bundle = AdminBundle::resolve();

    expect($this->get('/sivarcart/admin/assets/'.$bundle->version.'/admin.css')->assertOk())
        ->and($this->get('/sivarcart/admin/assets/'.$bundle->version.'/admin.js')->assertOk());
});

it('answers 404 for a piece of the bundle that does not exist', function () {
    $this->get('/sivarcart/admin/assets/'.AdminBundle::version().'/una-pieza-que-no-existe.js')
        ->assertNotFound();
});

it('does not answer a file of the application as a piece of the bundle', function () {
    $this->get('/sivarcart/admin/assets/'.AdminBundle::version().'/..%2F..%2Fcomposer.json')
        ->assertNotFound();
});

it('puts the version of the bundle in the folder of the address', function () {
    $bundle = AdminBundle::resolve();

    $user = User::factory()->create();

    $user->assignStoreRole(StoreRole::factory()->create(['permissions' => ['*']]));

    $this->actingAs($user);

    expect($this->get('/sivarcart/admin/dashboard')->getContent())
        ->toContain('/sivarcart/admin/assets/'.$bundle->version.'/admin.js')
        ->not->toContain('build/assets');
});

it('keeps the version of the bundle in the address of the pieces of the bundle', function () {
    /*
    | The pieces of the bundle ask for each other with a relative address, so
    | the version has to be a folder and not a query of the address: a browser
    | that kept the bundle of a previous version would ask for the piece of
    | that version and would mix the two bundles.
    */
    $content = file_get_contents(dirname(__DIR__, 3).'/resources/dist/admin/admin.js');

    expect($content)->not->toBeFalse()
        ->and($content)->toContain('import');

    $chunk = glob(dirname(__DIR__, 3).'/resources/dist/admin/Index-*.js');

    expect($chunk)->not->toBeEmpty();

    $imports = [];

    foreach ($chunk as $file) {
        $contents = (string) file_get_contents($file);

        if (preg_match_all('/from"\.\/([^"]+)"/', $contents, $matches) === 0) {
            continue;
        }

        foreach ($matches[1] as $import) {
            $imports[] = $import;
        }
    }

    expect($imports)->toContain('admin.js');
});

it('uses the bundle of the package when the application has none of its own', function () {
    expect(AdminBundle::resolve()->belongsToPackage())->toBeTrue();

    $user = User::factory()->create();

    $user->assignStoreRole(StoreRole::factory()->create(['permissions' => ['*']]));

    $this->actingAs($user);

    expect($this->get('/sivarcart/admin/dashboard')->getContent())
        ->toContain('/sivarcart/admin/assets/')
        ->not->toContain('build/assets');
});

it('uses the bundle of the application when it has one of its own', function () {
    $directory = public_path('build');

    File::ensureDirectoryExists($directory.'/assets');

    File::put($directory.'/manifest.json', (string) json_encode([
        'resources/js/sivarcart/admin.tsx' => [
            'src' => 'resources/js/sivarcart/admin.tsx',
            'file' => 'assets/admin.js',
            'isEntry' => true,
        ],
    ]));

    File::put($directory.'/assets/admin.js', '// el bundle de la aplicación');

    expect(AdminBundle::resolve()->belongsToPackage())->toBeFalse();

    $user = User::factory()->create();

    $user->assignStoreRole(StoreRole::factory()->create(['permissions' => ['*']]));

    $this->actingAs($user);

    expect($this->get('/sivarcart/admin/dashboard')->getContent())
        ->toContain('build/assets/admin.js')
        ->not->toContain('/sivarcart/admin/assets/');
});

it('falls back to the bundle of the package when the manifest of the application is broken', function () {
    $directory = public_path('build');

    File::ensureDirectoryExists($directory);

    File::put($directory.'/manifest.json', 'esto no es un manifiesto');

    expect(AdminBundle::resolve()->belongsToPackage())->toBeTrue();
});

it('changes the version of the assets when the content of the bundle changes', function () {
    $file = sys_get_temp_dir().'/sivarcart-version-'.uniqid().'.js';

    file_put_contents($file, 'uno');

    $before = AdminBundle::versionOf([$file]);

    file_put_contents($file, 'dos');

    expect(AdminBundle::versionOf([$file]))->not->toBe($before);

    @unlink($file);
});

it('does not change the version of the assets when only the date of the file changes', function () {
    $file = sys_get_temp_dir().'/sivarcart-version-'.uniqid().'.js';

    file_put_contents($file, 'uno');

    $before = AdminBundle::versionOf([$file]);

    touch($file, time() + 120);

    expect(AdminBundle::versionOf([$file]))->toBe($before);

    @unlink($file);
});

it('gives the same version to the assets every time it reads them', function () {
    expect(AdminBundle::version())->toBe(AdminBundle::version())
        ->and(AdminBundle::version())->not->toBe('');
});
