<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;

/**
 * The directory the mailables are published to.
 */
$published = fn (): string => resource_path('views/vendor/sivarcart/mail');

it('publishes the mailables so the merchant can rewrite them', function () use ($published) {
    $this->artisan('sivarcart:publish-mailables')->assertSuccessful();

    expect(File::exists($published().'/order-placed.blade.php'))->toBeTrue()
        ->and(File::exists($published().'/order-paid.blade.php'))->toBeTrue()
        ->and(File::exists($published().'/order-shipped.blade.php'))->toBeTrue()
        ->and(File::exists($published().'/order-delivered.blade.php'))->toBeTrue()
        ->and(File::exists($published().'/order-cancelled.blade.php'))->toBeTrue()
        ->and(File::exists($published().'/order-refunded.blade.php'))->toBeTrue()
        ->and(File::exists($published().'/payment-failed.blade.php'))->toBeTrue();
});

it('does not overwrite a mailable the merchant already rewrote', function () use ($published) {
    $this->artisan('sivarcart:publish-mailables')->assertSuccessful();

    File::put($published().'/order-placed.blade.php', 'El correo del comerciante.');

    $this->artisan('sivarcart:publish-mailables')->assertSuccessful();

    expect(File::get($published().'/order-placed.blade.php'))->toBe('El correo del comerciante.');
});

it('overwrites the mailables when the merchant asks for it', function () use ($published) {
    $this->artisan('sivarcart:publish-mailables')->assertSuccessful();

    File::put($published().'/order-placed.blade.php', 'El correo del comerciante.');

    $this->artisan('sivarcart:publish-mailables', ['--force' => true])->assertSuccessful();

    expect(File::get($published().'/order-placed.blade.php'))->toContain('x-mail::message');
});
