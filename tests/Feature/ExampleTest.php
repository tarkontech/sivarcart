<?php

declare(strict_types=1);

use Illuminate\Contracts\Console\Kernel;
use Tarkontech\SivarCart\SivarCart;

it('resolves the singleton', function () {
    expect(app(SivarCart::class))->toBeInstanceOf(SivarCart::class);
});

it('returns the same instance from the container', function () {
    expect(app(SivarCart::class))->toBe(app(SivarCart::class));
});

it('merges the package config', function () {
    expect(config('sivarcart.store.currency'))->toBe('USD');
    expect(config('sivarcart.store.timezone'))->toBe('America/El_Salvador');
    expect(config('sivarcart.store.prices_include_tax'))->toBeTrue();
    expect(config('sivarcart.store.default_tax_rate'))->toBe(0.13);
});

it('lets the host application override the package config', function () {
    config()->set('sivarcart.store.currency', 'USD');

    expect(config('sivarcart.store.currency'))->toBe('USD');
});

it('loads the package translations', function () {
    expect(trans('sivarcart::messages.order_notifications.order_placed.label'))
        ->toBe('Order received');
});

it('loads the package views', function () {
    expect(view()->exists('sivarcart::placeholder'))->toBeTrue();
});

it('registers the artisan command', function () {
    expect(array_key_exists('sivarcart:install', $this->app[Kernel::class]->all()))->toBeTrue();
});

it('registers the command that publishes the mailables', function () {
    expect(array_key_exists('sivarcart:publish-mailables', $this->app[Kernel::class]->all()))->toBeTrue();
});
