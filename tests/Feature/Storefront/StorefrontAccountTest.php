<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Inertia\Testing\AssertableInertia as Assert;
use SivarcartCore\SivarcartCore\Models\Customer;
use SivarcartCore\SivarcartCore\Models\Store;
use SivarcartCore\SivarcartCore\Notifications\AccountPasswordResetNotification;

it('shows the screen of entry', function () {
    $this->get('/tienda/cuenta/entrar')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Storefront/Account/Login')
            ->has('email'),
        );
});

it('signs a buyer in', function () {
    Store::factory()->create();

    $customer = Customer::factory()->create(['password' => 'una-clave-larga']);

    $this->post('/tienda/cuenta/entrar', [
        'email' => $customer->email,
        'password' => 'una-clave-larga',
    ])->assertRedirect(route('sivarcart-core.storefront.account.show'));

    expect(auth()->guard('sivarcart')->check())->toBeTrue();
});

it('does not sign a buyer in with the wrong password', function () {
    Store::factory()->create();

    $customer = Customer::factory()->create(['password' => 'una-clave-larga']);

    $this->post('/tienda/cuenta/entrar', [
        'email' => $customer->email,
        'password' => 'otra-clave',
    ])->assertSessionHasErrors('email');

    expect(auth()->guard('sivarcart')->check())->toBeFalse();
});

it('sends the buyer back to where it was going after signing in', function () {
    Store::factory()->create();

    $customer = Customer::factory()->create(['password' => 'una-clave-larga']);

    $this->get('/tienda/carrito')->assertRedirect();

    $this->post('/tienda/cuenta/entrar', [
        'email' => $customer->email,
        'password' => 'una-clave-larga',
        'redirect' => url('/tienda/carrito'),
    ])->assertRedirect(url('/tienda/carrito'));
});

it('does not send the buyer to another site after signing in', function () {
    Store::factory()->create();

    $customer = Customer::factory()->create(['password' => 'una-clave-larga']);

    $this->post('/tienda/cuenta/entrar', [
        'email' => $customer->email,
        'password' => 'una-clave-larga',
        'redirect' => 'https://otro-sitio.example.com',
    ])->assertRedirect(route('sivarcart-core.storefront.account.show'));
});

it('does not send the buyer to another site through a protocol-relative link', function () {
    Store::factory()->create();

    $customer = Customer::factory()->create(['password' => 'una-clave-larga']);

    $this->post('/tienda/cuenta/entrar', [
        'email' => $customer->email,
        'password' => 'una-clave-larga',
        'redirect' => '//otro-sitio.example.com',
    ])->assertRedirect(route('sivarcart-core.storefront.account.show'));
});

it('shows the screen of the signup', function () {
    $this->get('/tienda/cuenta/registro')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Storefront/Account/Register')
            ->has('document_types'),
        );
});

it('creates the account of a buyer and signs it in', function () {
    Store::factory()->create();

    $this->post('/tienda/cuenta/registro', [
        'first_name' => 'Ana',
        'last_name' => 'Guzmán',
        'email' => 'ana@example.com',
        'password' => 'una-clave-larga',
        'password_confirmation' => 'una-clave-larga',
    ])->assertRedirect(route('sivarcart-core.storefront.account.show'));

    expect(Customer::query()->where('email', 'ana@example.com')->exists())->toBeTrue()
        ->and(auth()->guard('sivarcart')->check())->toBeTrue();
});

it('says so when the account already exists', function () {
    Store::factory()->create();

    Customer::factory()->create(['email' => 'ana@example.com']);

    $this->post('/tienda/cuenta/registro', [
        'first_name' => 'Ana',
        'email' => 'ana@example.com',
        'password' => 'una-clave-larga',
        'password_confirmation' => 'una-clave-larga',
    ])->assertSessionHasErrors('email');
});

it('signs a buyer out', function () {
    shopper();

    $this->post('/tienda/cuenta/salir')
        ->assertRedirect(route('sivarcart-core.storefront.home'));

    expect(auth()->guard('sivarcart')->check())->toBeFalse();
});

it('shows the screen that asks for the link of the reset', function () {
    $this->get('/tienda/cuenta/recuperar')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Storefront/Account/ForgotPassword'));
});

it('sends the link that resets the password', function () {
    Store::factory()->create();

    Notification::fake();

    $customer = Customer::factory()->create();

    $this->post('/tienda/cuenta/recuperar', ['email' => $customer->email])
        ->assertRedirect();

    Notification::assertSentTo($customer, AccountPasswordResetNotification::class);
});

it('says the same thing when the account does not exist', function () {
    Store::factory()->create();

    Notification::fake();

    $this->post('/tienda/cuenta/recuperar', ['email' => 'nadie@example.com'])
        ->assertRedirect()
        ->assertSessionHasNoErrors();
});

it('shows the screen where the password is changed', function () {
    $this->get('/tienda/cuenta/recuperar/un-token?email=ana@example.com')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Storefront/Account/ResetPassword')
            ->where('token', 'un-token')
            ->where('email', 'ana@example.com'),
        );
});

it('changes the password of a buyer with the token of the reset', function () {
    Store::factory()->create();

    $customer = Customer::factory()->create();

    $token = Password::broker('sivarcart')->createToken($customer);

    $this->post('/tienda/cuenta/recuperar/'.$token, [
        'email' => $customer->email,
        'password' => 'una-clave-nueva',
        'password_confirmation' => 'una-clave-nueva',
    ])->assertRedirect(route('sivarcart-core.storefront.account.login'));

    expect(Hash::check('una-clave-nueva', $customer->fresh()->password))->toBeTrue();
});

it('says so when the token of the reset is not valid', function () {
    Store::factory()->create();

    $customer = Customer::factory()->create();

    $this->post('/tienda/cuenta/recuperar/un-token-que-no-existe', [
        'email' => $customer->email,
        'password' => 'una-clave-nueva',
        'password_confirmation' => 'una-clave-nueva',
    ])->assertSessionHasErrors('email');
});

it('shows the account of the buyer', function () {
    $customer = shopper();

    $this->get('/tienda/cuenta')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Storefront/Account/Index')
            ->where('buyer.email', $customer->email)
            ->has('addresses'),
        );
});

it('updates the profile of the buyer', function () {
    $customer = shopper();

    $this->put('/tienda/cuenta', [
        'first_name' => 'Ana',
        'last_name' => 'Guzmán',
        'phone' => '7000-0000',
    ])->assertRedirect();

    expect($customer->fresh()->first_name)->toBe('Ana');
});

it('changes the password of the buyer', function () {
    $customer = shopper();

    $customer->update(['password' => 'una-clave-larga']);

    $this->put('/tienda/cuenta/clave', [
        'current_password' => 'una-clave-larga',
        'password' => 'una-clave-nueva',
        'password_confirmation' => 'una-clave-nueva',
    ])->assertRedirect();

    expect(Hash::check('una-clave-nueva', $customer->fresh()->password))->toBeTrue();
});

it('does not change the password with the wrong current one', function () {
    $customer = shopper();

    $customer->update(['password' => 'una-clave-larga']);

    $this->put('/tienda/cuenta/clave', [
        'current_password' => 'otra-clave',
        'password' => 'una-clave-nueva',
        'password_confirmation' => 'una-clave-nueva',
    ])->assertSessionHasErrors('current_password');

    expect(Hash::check('una-clave-larga', $customer->fresh()->password))->toBeTrue();
});
