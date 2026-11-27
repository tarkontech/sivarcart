<?php

declare(strict_types=1);

use SivarcartCore\SivarcartCore\Models\Store;
use SivarcartCore\SivarcartCore\Storefront\StorefrontTheme;

it('puts the tokens of la tienda in the page', function () {
    Store::factory()->create();

    config()->set('sivarcart-core.storefront.theme', [
        'primary' => '#ff0000',
        'radius' => '0.75rem',
    ]);

    expect($this->get('/tienda')->getContent())
        ->toContain('--primary: #ff0000')
        ->toContain('--radius: 0.75rem');
});

it('puts the tokens of la tienda in the dark theme after the light one', function () {
    Store::factory()->create();

    config()->set('sivarcart-core.storefront.theme', [
        'primary' => '#ff0000',
    ]);

    config()->set('sivarcart-core.storefront.theme_dark', [
        'primary' => '#00ff00',
    ]);

    $content = $this->get('/tienda')->getContent();

    expect($content)->toContain('--primary: #00ff00');
});

it('reads the tokens of la configuration', function () {
    config()->set('sivarcart-core.storefront.theme', [
        'primary' => '#ff0000',
        'font' => 'Inter, sans-serif',
    ]);

    expect(StorefrontTheme::variables())->toBe([
        '--primary' => '#ff0000',
        '--font-sans' => 'Inter, sans-serif',
    ]);
});

it('leaves out the tokens that are empty', function () {
    config()->set('sivarcart-core.storefront.theme', [
        'primary' => '',
        'radius' => '0.5rem',
    ]);

    expect(StorefrontTheme::variables())->toBe(['--radius' => '0.5rem']);
});

it('does not mix the theme of la tienda with the theme of the panel', function () {
    config()->set('sivarcart-core.storefront.theme', [
        'primary' => '#ff0000',
    ]);

    config()->set('sivarcart-core.admin.theme', [
        'primary' => '#0000ff',
    ]);

    expect(StorefrontTheme::variables())->toBe(['--primary' => '#ff0000']);
});
