<?php

declare(strict_types=1);

use Tarkontech\SivarCart\Storefront\StorefrontBundle;

it('serves the stylesheet of the store', function () {
    $response = $this->get('/tienda/assets/'.StorefrontBundle::version().'/storefront.css');

    $response->assertOk();

    expect($response->headers->get('content-type'))->toContain('text/css');
});

it('serves the script of the store', function () {
    $response = $this->get('/tienda/assets/'.StorefrontBundle::version().'/storefront.js');

    $response->assertOk();

    expect($response->headers->get('content-type'))->toContain('text/javascript');
});

it('serves the stylesheet and the script of the store with the same version', function () {
    $bundle = StorefrontBundle::resolve();

    expect($this->get('/tienda/assets/'.$bundle->version.'/storefront.css')->assertOk())
        ->and($this->get('/tienda/assets/'.$bundle->version.'/storefront.js')->assertOk());
});

it('answers 404 for a piece of the bundle that does not exist', function () {
    $this->get('/tienda/assets/'.StorefrontBundle::version().'/una-pieza-que-no-existe.js')
        ->assertNotFound();
});

it('does not answer a file of the application as a piece of the bundle', function () {
    $this->get('/tienda/assets/'.StorefrontBundle::version().'/..%2F..%2Fcomposer.json')
        ->assertNotFound();
});

it('puts the version of the bundle in the folder of the address', function () {
    $bundle = StorefrontBundle::resolve();

    expect($this->get('/tienda')->getContent())
        ->toContain('/tienda/assets/'.$bundle->version.'/storefront.js')
        ->not->toContain('build/assets');
});

it('keeps the version of the bundle in the address of the pieces of the bundle', function () {
    /*
    | The pieces of the bundle ask for each other with a relative address, so
    | the version has to be a folder and not a query of the address: a browser
    | that kept the bundle of a previous version would ask for the piece of
    | that version and would mix the two bundles.
    */
    $content = file_get_contents(dirname(__DIR__, 3).'/resources/dist/storefront/storefront.js');

    expect($content)->not->toBeFalse()
        ->and($content)->toContain('import');

    $chunk = glob(dirname(__DIR__, 3).'/resources/dist/storefront/Index-*.js');

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

    expect($imports)->toContain('storefront.js');
});

it('uses the bundle of the package when the application has none of its own', function () {
    expect(StorefrontBundle::resolve()->belongsToPackage())->toBeTrue();

    expect($this->get('/tienda')->getContent())
        ->toContain('/tienda/assets/')
        ->not->toContain('build/assets');
});

it('gives the same version to the assets every time it reads them', function () {
    expect(StorefrontBundle::version())->toBe(StorefrontBundle::version())
        ->and(StorefrontBundle::version())->not->toBe('');
});
