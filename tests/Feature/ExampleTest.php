<?php

declare(strict_types=1);

use Illuminate\Contracts\Console\Kernel;
use SivarcartCore\SivarcartCore\SivarcartCore;

it('resolves the singleton', function () {
    expect(app(SivarcartCore::class))->toBeInstanceOf(SivarcartCore::class);
});

it('returns the same instance from the container', function () {
    expect(app(SivarcartCore::class))->toBe(app(SivarcartCore::class));
});

it('merges the package config', function () {
    expect(config('sivarcart-core.store.currency'))->toBe('USD');
    expect(config('sivarcart-core.store.timezone'))->toBe('America/El_Salvador');
    expect(config('sivarcart-core.store.prices_include_tax'))->toBeTrue();
    expect(config('sivarcart-core.store.default_tax_rate'))->toBe(0.13);
});

it('lets the host application override the package config', function () {
    config()->set('sivarcart-core.store.currency', 'USD');

    expect(config('sivarcart-core.store.currency'))->toBe('USD');
});

it('loads the package translations', function () {
    expect(trans('sivarcart-core::messages.placeholder'))->toBe('SivarcartCore placeholder translation.');
});

it('loads the package views', function () {
    expect(view()->exists('sivarcart-core::placeholder'))->toBeTrue();
});

it('registers the artisan command', function () {
    expect(array_key_exists('sivarcart-core:install', $this->app[Kernel::class]->all()))->toBeTrue();
});
