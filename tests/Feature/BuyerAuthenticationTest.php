<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Auth;
use Tarkontech\SivarCart\Models\Customer;
use Tarkontech\SivarCart\Services\CustomerService;
use Tarkontech\SivarCart\Support\Auth as StoreAuth;
use Workbench\App\Models\User;

it('registers the guard and the provider of the buyers', function () {
    expect(config('auth.guards.sivarcart'))->toBe([
        'driver' => 'session',
        'provider' => 'sivarcart',
    ])
        ->and(config('auth.providers.sivarcart.model'))->toBe(Customer::class)
        ->and(config('auth.providers.sivarcart.driver'))->toBe('eloquent');
});

it('registers the broker of the passwords of the buyers', function () {
    expect(config('auth.passwords.sivarcart'))->toBe([
        'provider' => 'sivarcart',
        'table' => 'sivarcart_password_reset_tokens',
        'expire' => 60,
    ]);
});

it('reads the name of the guard from the configuration of the package', function () {
    expect((string) config('sivarcart.buyers.guard'))->toBe('sivarcart')
        ->and((string) config('sivarcart.buyers.provider'))->toBe('sivarcart');
});

it('signs a buyer in with the guard of the package', function () {
    Customer::factory()->create([
        'email' => 'ana@example.test',
        'password' => 'secreto123',
    ]);

    $signed = Auth::guard('sivarcart')->attempt([
        'email' => 'ana@example.test',
        'password' => 'secreto123',
    ]);

    expect($signed)->toBeTrue()
        ->and(Auth::guard('sivarcart')->user())->toBeInstanceOf(Customer::class)
        ->and(Auth::guard('sivarcart')->user()->email)->toBe('ana@example.test');
});

it('does not sign a buyer in with a wrong password', function () {
    Customer::factory()->create([
        'email' => 'ana@example.test',
        'password' => 'secreto123',
    ]);

    $signed = Auth::guard('sivarcart')->attempt([
        'email' => 'ana@example.test',
        'password' => 'otracosa',
    ]);

    expect($signed)->toBeFalse()
        ->and(Auth::guard('sivarcart')->user())->toBeNull();
});

it('keeps the buyer and the staff of the store in different guards', function () {
    Customer::factory()->create([
        'email' => 'ana@example.test',
        'password' => 'secreto123',
    ]);

    $staff = User::factory()->create();

    Auth::guard('web')->login($staff);
    Auth::guard('sivarcart')->attempt([
        'email' => 'ana@example.test',
        'password' => 'secreto123',
    ]);

    expect(Auth::guard('web')->user())->toBeInstanceOf(User::class)
        ->and(Auth::guard('sivarcart')->user())->toBeInstanceOf(Customer::class);
});

it('leaves the staff of the store in the hands of the application', function () {
    expect(StoreAuth::model())->toBe(User::class)
        ->and(StoreAuth::guard())->toBe('web');
});

it('reads the buyer that is signed in through the service', function () {
    $customer = Customer::factory()->create([
        'email' => 'ana@example.test',
        'password' => 'secreto123',
    ]);

    $customers = app(CustomerService::class);

    $customers->attempt('ana@example.test', 'secreto123');

    expect($customers->user()?->is($customer))->toBeTrue();
});
