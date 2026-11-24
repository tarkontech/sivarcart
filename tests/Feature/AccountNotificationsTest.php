<?php

declare(strict_types=1);

use Illuminate\Contracts\Mail\Factory as MailFactory;
use Illuminate\Mail\Mailer;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use SivarcartCore\SivarcartCore\Mail\AccountPasswordResetMail;
use SivarcartCore\SivarcartCore\Mail\AccountVerifyEmailMail;
use SivarcartCore\SivarcartCore\Mail\AccountWelcomeMail;
use SivarcartCore\SivarcartCore\Models\Customer;
use SivarcartCore\SivarcartCore\Notifications\AccountEmailVerificationNotification;
use SivarcartCore\SivarcartCore\Notifications\AccountPasswordResetNotification;
use SivarcartCore\SivarcartCore\Notifications\AccountWelcomeNotification;
use SivarcartCore\SivarcartCore\Services\CustomerService;

it('welcomes the buyer and asks it to verify the email when the account is created', function () {
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

it('writes the welcome email to the buyer', function () {
    $customer = Customer::factory()->create([
        'first_name' => 'Ana',
        'email' => 'ana@example.test',
    ]);

    $mail = new AccountWelcomeMail($customer);

    $mail->assertTo('ana@example.test');
    $mail->assertSeeInHtml('Ana');
});

it('writes the verification email with the signed link', function () {
    $customer = Customer::factory()->create(['email' => 'ana@example.test']);

    $mail = new AccountVerifyEmailMail($customer);

    $mail->assertTo('ana@example.test');
    $mail->assertSeeInHtml(url('/email/verify/'.$customer->getKey()));
});

it('writes the reset email with the link of the application', function () {
    $customer = Customer::factory()->create(['email' => 'ana@example.test']);

    $mail = new AccountPasswordResetMail($customer, 'un-token');

    $mail->assertTo('ana@example.test');
    $mail->assertSeeInHtml(url('/password/reset/un-token'));
});

it('sends the reset email to the buyer that asked for it', function () {
    Notification::fake();

    $customer = Customer::factory()->create(['email' => 'ana@example.test']);

    app(CustomerService::class)->sendPasswordResetLink('ana@example.test');

    Notification::assertSentTo($customer, AccountPasswordResetNotification::class);
});

it('signs the link of the verification', function () {
    $customer = Customer::factory()->create(['email' => 'ana@example.test']);

    $url = URL::temporarySignedRoute('verification.verify', now()->addHour(), [
        'id' => $customer->getKey(),
        'hash' => $customer->emailVerificationHash(),
    ]);

    expect($url)->toContain('signature=')
        ->and($url)->toContain('expires=');
});

it('says out loud when the application did not declare the route of the link', function () {
    config()->set('sivarcart-core.buyers.email_verification_route', 'una-ruta-que-no-existe');

    $customer = Customer::factory()->create(['email' => 'ana@example.test']);

    expect(fn () => (new AccountVerifyEmailMail($customer))->content())
        ->toThrow(RuntimeException::class, 'una-ruta-que-no-existe');
});

it('keeps the account when the mailer is down', function () {
    $this->app->bind(MailFactory::class, fn (): MailFactory => new class implements MailFactory
    {
        public function mailer($name = null)
        {
            throw new RuntimeException('The mailer is down.');
        }
    });

    $customer = app(CustomerService::class)->register([
        'first_name' => 'Ana',
        'email' => 'ana@example.test',
        'password' => 'secreto123',
    ]);

    expect($customer->fresh())->not->toBeNull()
        ->and($customer->fresh()->email)->toBe('ana@example.test');
});

it('delivers the emails of the account to the buyer', function () {
    config()->set('mail.default', 'array');

    app(CustomerService::class)->register([
        'first_name' => 'Ana',
        'email' => 'ana@example.test',
        'password' => 'secreto123',
    ]);

    $transport = app(Mailer::class)->getSymfonyTransport();

    expect($transport->messages())->toHaveCount(2);
});
