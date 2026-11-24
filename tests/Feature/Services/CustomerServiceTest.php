<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use SivarcartCore\SivarcartCore\Enums\CustomerDocumentType;
use SivarcartCore\SivarcartCore\Exceptions\CustomerAlreadyExists;
use SivarcartCore\SivarcartCore\Exceptions\InvalidDocument;
use SivarcartCore\SivarcartCore\Exceptions\SivarcartCoreException;
use SivarcartCore\SivarcartCore\Models\Customer;
use SivarcartCore\SivarcartCore\Models\CustomerAddress;
use SivarcartCore\SivarcartCore\Models\Department;
use SivarcartCore\SivarcartCore\Models\Order;
use SivarcartCore\SivarcartCore\Notifications\AccountEmailVerificationNotification;
use SivarcartCore\SivarcartCore\Notifications\AccountPasswordResetNotification;
use SivarcartCore\SivarcartCore\Notifications\AccountWelcomeNotification;
use SivarcartCore\SivarcartCore\Services\CustomerService;

it('creates the account of a buyer', function () {
    $customer = app(CustomerService::class)->register([
        'first_name' => 'Ana',
        'last_name' => 'Guzmán',
        'email' => 'ana@example.test',
        'phone' => '70000000',
        'password' => 'secreto123',
    ]);

    expect($customer)->toBeInstanceOf(Customer::class)
        ->and($customer->first_name)->toBe('Ana')
        ->and($customer->last_name)->toBe('Guzmán')
        ->and($customer->email)->toBe('ana@example.test')
        ->and($customer->is_active)->toBeTrue()
        ->and(Customer::query()->count())->toBe(1);
});

it('hashes the password of the buyer', function () {
    $customer = app(CustomerService::class)->register([
        'first_name' => 'Ana',
        'email' => 'ana@example.test',
        'password' => 'secreto123',
    ]);

    expect($customer->password)->not->toBe('secreto123')
        ->and(Hash::check('secreto123', $customer->password))->toBeTrue();
});

it('refuses a second account with the same email', function () {
    app(CustomerService::class)->register([
        'first_name' => 'Ana',
        'email' => 'ana@example.test',
        'password' => 'secreto123',
    ]);

    expect(fn () => app(CustomerService::class)->register([
        'first_name' => 'Otra',
        'email' => 'ana@example.test',
        'password' => 'otracosa',
    ]))->toThrow(CustomerAlreadyExists::class);

    expect(Customer::query()->count())->toBe(1);
});

it('refuses an account without an email', function () {
    expect(fn () => app(CustomerService::class)->register([
        'first_name' => 'Ana',
        'password' => 'secreto123',
    ]))->toThrow(SivarcartCoreException::class);
});

it('refuses an account with a document that does not hold the format', function () {
    expect(fn () => app(CustomerService::class)->register([
        'first_name' => 'Ana',
        'email' => 'ana@example.test',
        'password' => 'secreto123',
        'document_type' => CustomerDocumentType::DUI,
        'document_number' => '01234567-9',
    ]))->toThrow(InvalidDocument::class);

    expect(Customer::query()->count())->toBe(0);
});

it('creates an account with a document that holds the format', function () {
    $customer = app(CustomerService::class)->register([
        'first_name' => 'Ana',
        'email' => 'ana@example.test',
        'password' => 'secreto123',
        'document_type' => CustomerDocumentType::DUI,
        'document_number' => '01234567-8',
    ]);

    expect($customer->document_type)->toBe(CustomerDocumentType::DUI)
        ->and($customer->document_number)->toBe('01234567-8');
});

it('updates the profile of a buyer without touching the password', function () {
    $customer = Customer::factory()->create(['password' => 'secreto123']);

    $updated = app(CustomerService::class)->updateProfile($customer, [
        'first_name' => 'Ana',
        'last_name' => 'Guzmán',
        'phone' => '70000001',
        'accepts_marketing' => true,
    ]);

    expect($updated->first_name)->toBe('Ana')
        ->and($updated->last_name)->toBe('Guzmán')
        ->and($updated->phone)->toBe('70000001')
        ->and($updated->accepts_marketing)->toBeTrue()
        ->and(Hash::check('secreto123', $updated->password))->toBeTrue();
});

it('changes the password of a buyer', function () {
    $customer = Customer::factory()->create(['password' => 'secreto123']);

    $updated = app(CustomerService::class)->changePassword($customer, 'otracosa');

    expect(Hash::check('otracosa', $updated->password))->toBeTrue()
        ->and(Hash::check('secreto123', $updated->password))->toBeFalse();
});

it('verifies the email of a buyer', function () {
    $customer = Customer::factory()->create(['email_verified_at' => null]);

    $verified = app(CustomerService::class)->verifyEmail($customer);

    expect($verified->email_verified_at)->not->toBeNull()
        ->and($verified->hasVerifiedEmail())->toBeTrue();
});

it('verifies the email with the link that was sent', function () {
    $customer = Customer::factory()->create(['email_verified_at' => null]);

    $verified = app(CustomerService::class)->verifyEmailFromLink(
        (int) $customer->getKey(),
        $customer->emailVerificationHash(),
    );

    expect($verified->email_verified_at)->not->toBeNull();
});

it('refuses a link of verification that belongs to nobody', function () {
    $customer = Customer::factory()->create(['email_verified_at' => null]);

    expect(fn () => app(CustomerService::class)->verifyEmailFromLink((int) $customer->getKey(), 'otra-cosa'))
        ->toThrow(SivarcartCoreException::class);

    expect($customer->fresh()->email_verified_at)->toBeNull();
});

it('gives the buyer the orders it placed as a guest', function () {
    $order = Order::factory()->guest()->create([
        'customer_email' => 'ana@example.test',
        'placed_at' => now()->subDay(),
    ]);

    $customer = app(CustomerService::class)->register([
        'first_name' => 'Ana',
        'email' => 'ana@example.test',
        'password' => 'secreto123',
    ]);

    expect($order->fresh()->customer_id)->toBe($customer->getKey())
        ->and($customer->fresh()->last_order_at)->not->toBeNull();
});

it('gives the buyer the orders it placed as a guest only once', function () {
    Order::factory()->guest()->create([
        'customer_email' => 'ana@example.test',
        'placed_at' => now()->subDay(),
    ]);

    $customer = Customer::factory()->create(['email' => 'ana@example.test']);

    app(CustomerService::class)->promoteGuest($customer);
    app(CustomerService::class)->promoteGuest($customer);

    expect(Order::query()->where('customer_id', $customer->getKey())->count())->toBe(1);
});

it('leaves the orders of another buyer alone', function () {
    $order = Order::factory()->guest()->create(['customer_email' => 'otro@example.test']);

    $customer = Customer::factory()->create(['email' => 'ana@example.test']);

    app(CustomerService::class)->promoteGuest($customer);

    expect($order->fresh()->customer_id)->toBeNull();
});

it('writes the moment of the last order of the buyer', function () {
    $customer = Customer::factory()->create(['last_order_at' => null]);

    $updated = app(CustomerService::class)->recordOrder($customer);

    expect($updated->last_order_at)->not->toBeNull();
});

it('signs a buyer in', function () {
    $customer = Customer::factory()->create([
        'email' => 'ana@example.test',
        'password' => 'secreto123',
    ]);

    $buyer = app(CustomerService::class)->attempt('ana@example.test', 'secreto123');

    expect($buyer)->toBeInstanceOf(Customer::class)
        ->and($buyer->is($customer))->toBeTrue()
        ->and(app(CustomerService::class)->user()?->is($customer))->toBeTrue();
});

it('does not sign a buyer in with the wrong password', function () {
    Customer::factory()->create([
        'email' => 'ana@example.test',
        'password' => 'secreto123',
    ]);

    expect(app(CustomerService::class)->attempt('ana@example.test', 'otracosa'))->toBeNull()
        ->and(app(CustomerService::class)->user())->toBeNull();
});

it('signs a buyer out', function () {
    Customer::factory()->create([
        'email' => 'ana@example.test',
        'password' => 'secreto123',
    ]);

    $customers = app(CustomerService::class);

    $customers->attempt('ana@example.test', 'secreto123');
    $customers->logout();

    expect($customers->user())->toBeNull();
});

it('saves the address of a buyer and resolves the department', function () {
    $department = Department::factory()->create(['name' => 'San Salvador', 'code' => 'SS']);
    $customer = Customer::factory()->create();

    $address = app(CustomerService::class)->saveAddress($customer, [
        'first_name' => 'Ana',
        'address_line1' => 'Calle 1',
        'municipality' => 'San Salvador',
        'department' => 'San Salvador',
    ]);

    expect($address)->toBeInstanceOf(CustomerAddress::class)
        ->and($address->department_id)->toBe($department->getKey())
        ->and($address->is_default)->toBeTrue()
        ->and($customer->addresses()->count())->toBe(1);
});

it('keeps one address as the default one', function () {
    $customer = Customer::factory()->create();
    $customers = app(CustomerService::class);

    $first = $customers->saveAddress($customer, ['first_name' => 'Ana', 'address_line1' => 'Calle 1']);
    $second = $customers->saveAddress($customer, ['first_name' => 'Ana', 'address_line1' => 'Calle 2']);

    expect($first->fresh()->is_default)->toBeFalse()
        ->and($second->fresh()->is_default)->toBeTrue()
        ->and($customers->defaultAddress($customer)?->is($second))->toBeTrue();
});

it('sends the link that resets the password', function () {
    Notification::fake();

    $customer = Customer::factory()->create([
        'email' => 'ana@example.test',
        'password' => 'secreto123',
    ]);

    expect(app(CustomerService::class)->sendPasswordResetLink('ana@example.test'))->toBeTrue();

    Notification::assertSentTo($customer, AccountPasswordResetNotification::class);
});

it('says that there is no account to reset when the email is unknown', function () {
    Notification::fake();

    expect(app(CustomerService::class)->sendPasswordResetLink('nadie@example.test'))->toBeFalse();

    Notification::assertNothingSent();
});

it('sets a new password with the token of a reset', function () {
    Notification::fake();

    $customer = Customer::factory()->create([
        'email' => 'ana@example.test',
        'password' => 'secreto123',
    ]);

    $customers = app(CustomerService::class);

    $customers->sendPasswordResetLink('ana@example.test');

    // The broker keeps a hash of the token, so the plain one is the one that
    // travelled to the buyer.
    $notification = Notification::sent($customer, AccountPasswordResetNotification::class)->first();

    $customers->resetPassword('ana@example.test', $notification->token, 'otracosa');

    expect(Hash::check('otracosa', $customer->fresh()->password))->toBeTrue();
});

it('refuses a token of reset that does not exist', function () {
    Customer::factory()->create([
        'email' => 'ana@example.test',
        'password' => 'secreto123',
    ]);

    expect(fn () => app(CustomerService::class)->resetPassword('ana@example.test', 'inventado', 'otracosa'))
        ->toThrow(SivarcartCoreException::class);
});

it('welcomes the buyer and asks it to verify the email', function () {
    Notification::fake();

    app(CustomerService::class)->register([
        'first_name' => 'Ana',
        'email' => 'ana@example.test',
        'password' => 'secreto123',
    ]);

    $customer = Customer::query()->where('email', 'ana@example.test')->firstOrFail();

    Notification::assertSentTo($customer, AccountWelcomeNotification::class);
    Notification::assertSentTo($customer, AccountEmailVerificationNotification::class);
});
