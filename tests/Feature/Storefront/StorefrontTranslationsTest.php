<?php

declare(strict_types=1);

use Illuminate\Support\Arr;

it('has the same keys in every language', function () {
    $english = Arr::dot(require __DIR__.'/../../../lang/en/storefront.php');
    $spanish = Arr::dot(require __DIR__.'/../../../lang/es/storefront.php');

    expect(array_keys($english))->toEqual(array_keys($spanish));
});

it('serves the same texts for es_SV and for es', function () {
    $spanish = require __DIR__.'/../../../lang/es/storefront.php';
    $salvadoran = require __DIR__.'/../../../lang/es_SV/storefront.php';

    expect($salvadoran)->toEqual($spanish);
});

it('speaks the language of the application', function () {
    app()->setLocale('es');

    expect(trans('sivarcart-core::storefront.cart.title'))->toBe('Carrito');
});

it('speaks the language of the store when the application uses es_SV', function () {
    app()->setLocale('es_SV');

    expect(trans('sivarcart-core::storefront.cart.title'))->toBe('Carrito');
});

it('has the texts that the pages of the store ask for', function () {
    $text = trans('sivarcart-core::storefront');

    expect($text)->toBeArray();

    foreach ([
        'title', 'nav', 'home', 'catalog', 'product', 'cart', 'checkout',
        'orders', 'account', 'auth', 'common',
    ] as $section) {
        expect($text)->toHaveKey($section);
    }
});
