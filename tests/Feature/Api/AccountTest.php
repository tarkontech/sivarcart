<?php

declare(strict_types=1);

use Tarkontech\SivarCart\Enums\CustomerDocumentType;
use Tarkontech\SivarCart\Models\Customer;
use Tarkontech\SivarCart\Models\CustomerAddress;

/**
 * The account of a buyer that signs up through the API.
 */
function signup(): Customer
{
    $response = test()->postJson('/api/sivarcart/account/register', [
        'first_name' => 'Ana',
        'last_name' => 'Guzmán',
        'email' => 'ana@example.test',
        'password' => 'secreto123',
        'password_confirmation' => 'secreto123',
    ]);

    $response->assertCreated();

    return Customer::query()->where('email', 'ana@example.test')->firstOrFail();
}

it('creates the account of a buyer', function () {
    $response = $this->postJson('/api/sivarcart/account/register', [
        'first_name' => 'Ana',
        'last_name' => 'Guzmán',
        'email' => 'ana@example.test',
        'password' => 'secreto123',
        'password_confirmation' => 'secreto123',
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.email', 'ana@example.test')
        ->assertJsonPath('data.name', 'Ana Guzmán')
        ->assertJsonMissingPath('data.password');

    $customer = Customer::query()->where('email', 'ana@example.test')->firstOrFail();

    expect($customer->password)->not->toBe('secreto123');
});

it('does not create the account of a buyer whose email is taken', function () {
    signup();

    $this->postJson('/api/sivarcart/account/register', [
        'first_name' => 'Ana',
        'email' => 'ana@example.test',
        'password' => 'secreto123',
        'password_confirmation' => 'secreto123',
    ])
        ->assertStatus(409)
        ->assertJsonPath('code', 'customer_already_exists');
});

it('does not create the account of a buyer with an invalid document', function () {
    $this->postJson('/api/sivarcart/account/register', [
        'first_name' => 'Ana',
        'email' => 'ana@example.test',
        'password' => 'secreto123',
        'password_confirmation' => 'secreto123',
        'document_type' => CustomerDocumentType::DUI->value,
        'document_number' => '12345678',
    ])
        ->assertStatus(422)
        ->assertJsonPath('code', 'invalid_document');
});

it('refuses a signup that is not complete', function () {
    $this->postJson('/api/sivarcart/account/register', [
        'email' => 'ana@example.test',
    ])->assertStatus(422);
});

it('signs a buyer in', function () {
    Customer::factory()->create([
        'email' => 'ana@example.test',
        'password' => 'secreto123',
    ]);

    $this->postJson('/api/sivarcart/account/login', [
        'email' => 'ana@example.test',
        'password' => 'secreto123',
    ])
        ->assertOk()
        ->assertJsonPath('data.email', 'ana@example.test');
});

it('does not say if the email exists when the credentials do not match', function () {
    Customer::factory()->create([
        'email' => 'ana@example.test',
        'password' => 'secreto123',
    ]);

    $this->postJson('/api/sivarcart/account/login', [
        'email' => 'ana@example.test',
        'password' => 'otracosa',
    ])
        ->assertStatus(422)
        ->assertJsonPath('code', 'sivar_cart_exception')
        ->assertJsonMissingPath('data');
});

it('signs a buyer out', function () {
    $customer = Customer::factory()->create();
    $this->actingAs($customer, 'sivarcart');

    $this->postJson('/api/sivarcart/account/logout')->assertNoContent();

    $this->getJson('/api/sivarcart/account')->assertUnauthorized();
});

it('shows the account of the buyer that is signed in', function () {
    $customer = Customer::factory()->create(['first_name' => 'Ana']);
    $this->actingAs($customer, 'sivarcart');

    $this->getJson('/api/sivarcart/account')
        ->assertOk()
        ->assertJsonPath('data.id', $customer->uuid)
        ->assertJsonPath('data.first_name', 'Ana');
});

it('asks for a buyer', function () {
    $this->getJson('/api/sivarcart/account')->assertUnauthorized();
});

it('updates the profile of the buyer', function () {
    $customer = Customer::factory()->create();
    $this->actingAs($customer, 'sivarcart');

    $this->patchJson('/api/sivarcart/account', [
        'first_name' => 'Ana',
        'last_name' => 'Guzmán',
        'phone' => '7000-0000',
    ])
        ->assertOk()
        ->assertJsonPath('data.first_name', 'Ana')
        ->assertJsonPath('data.last_name', 'Guzmán')
        ->assertJsonPath('data.phone', '7000-0000');
});

it('changes the password of the buyer', function () {
    $customer = Customer::factory()->create(['password' => 'secreto123']);
    $this->actingAs($customer, 'sivarcart');

    $this->putJson('/api/sivarcart/account/password', [
        'current_password' => 'secreto123',
        'password' => 'otrasecreto123',
        'password_confirmation' => 'otrasecreto123',
    ])->assertOk();

    expect($customer->fresh()->password)->not->toBe('otrasecreto123');
});

it('does not change the password when the current one does not match', function () {
    $customer = Customer::factory()->create(['password' => 'secreto123']);
    $this->actingAs($customer, 'sivarcart');

    $this->putJson('/api/sivarcart/account/password', [
        'current_password' => 'otracosa',
        'password' => 'otrasecreto123',
        'password_confirmation' => 'otrasecreto123',
    ])
        ->assertStatus(422)
        ->assertJsonPath('code', 'sivar_cart_exception');

    expect($customer->fresh()->password)->not->toBe('otrasecreto123');
});

it('lists the addresses of the buyer', function () {
    $customer = Customer::factory()->create();
    CustomerAddress::factory()->create([
        'customer_id' => $customer->getKey(),
        'address_line1' => 'Calle 1',
        'municipality' => 'San Salvador',
    ]);

    $this->actingAs($customer, 'sivarcart');

    $this->getJson('/api/sivarcart/account/addresses')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.address_line1', 'Calle 1');
});

it('sends the link that resets the password', function () {
    Customer::factory()->create(['email' => 'ana@example.test']);

    $this->postJson('/api/sivarcart/account/password/email', ['email' => 'ana@example.test'])
        ->assertStatus(202);
});

it('answers the same when the account of the email does not exist', function () {
    $this->postJson('/api/sivarcart/account/password/email', ['email' => 'nadie@example.test'])
        ->assertStatus(202);
});
