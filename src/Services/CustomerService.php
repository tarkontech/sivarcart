<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Services;

use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Contracts\Auth\PasswordBroker;
use Illuminate\Support\Facades\Auth as AuthFacade;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use SivarcartCore\SivarcartCore\Enums\CustomerDocumentType;
use SivarcartCore\SivarcartCore\Exceptions\CustomerAlreadyExists;
use SivarcartCore\SivarcartCore\Exceptions\InvalidDocument;
use SivarcartCore\SivarcartCore\Exceptions\SivarcartCoreException;
use SivarcartCore\SivarcartCore\Models\Customer;
use SivarcartCore\SivarcartCore\Models\CustomerAddress;
use SivarcartCore\SivarcartCore\Models\Department;
use SivarcartCore\SivarcartCore\Models\Order;
use SivarcartCore\SivarcartCore\Notifications\AccountWelcomeNotification;
use SivarcartCore\SivarcartCore\Rules\CustomerDocument;

/**
 * Keeps the account of the buyers of the store.
 *
 * A buyer signs in with the guard of the package, so the credentials, the
 * session and the password of a buyer belong here and not to the application
 * that installs the package. The staff of the store are a different thing:
 * they are the users of the application.
 *
 * The account is the identity of the buyer: the orders carry it, the address
 * of the buyer hangs from it and the buyer cannot place an order without it.
 */
final class CustomerService
{
    /**
     * Create the account of a buyer.
     *
     * The email address is the identity of the buyer, so a second account
     * with the same address is refused. The password is written by the cast
     * of the model, which hashes it.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function register(array $attributes): Customer
    {
        $email = $this->email($attributes);

        if ($email === null) {
            throw new SivarcartCoreException('La cuenta necesita un correo electrónico.');
        }

        $password = $this->string($attributes, 'password');

        if ($password === null) {
            throw new SivarcartCoreException('La cuenta necesita una contraseña.');
        }

        $this->assertDocumentIsValid($attributes);

        if ($this->findByEmail($email) !== null) {
            throw CustomerAlreadyExists::withEmail($email);
        }

        $customer = Customer::query()->create([
            'first_name' => $this->string($attributes, 'first_name') ?? '',
            'last_name' => $this->string($attributes, 'last_name'),
            'email' => $email,
            'phone' => $this->string($attributes, 'phone'),
            'document_type' => $this->documentType($attributes),
            'document_number' => $this->string($attributes, 'document_number'),
            'password' => $password,
            'accepts_marketing' => (bool) ($attributes['accepts_marketing'] ?? false),
            'is_active' => true,
        ]);

        $this->promoteGuest($customer);

        $customer->notifyBuyer(new AccountWelcomeNotification($customer));
        $customer->sendEmailVerificationNotification();

        return $customer;
    }

    /**
     * Update the profile of a buyer.
     *
     * The email address is not part of the profile: changing it needs a new
     * verification, so it has a flow of its own.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function updateProfile(Customer $customer, array $attributes): Customer
    {
        $this->assertDocumentIsValid($attributes);

        $customer->fill([
            'first_name' => $this->string($attributes, 'first_name') ?? $customer->first_name,
            'last_name' => $this->string($attributes, 'last_name'),
            'phone' => $this->string($attributes, 'phone'),
            'document_type' => $this->documentType($attributes) ?? $customer->document_type,
            'document_number' => $this->string($attributes, 'document_number'),
            'accepts_marketing' => (bool) ($attributes['accepts_marketing'] ?? $customer->accepts_marketing),
        ]);

        $customer->save();

        return $customer;
    }

    /**
     * Change the password of a buyer.
     *
     * The cast of the model hashes the password.
     */
    public function changePassword(Customer $customer, string $password): Customer
    {
        $customer->password = $password;
        $customer->save();

        return $customer;
    }

    /**
     * Whether the password is the one of the buyer.
     *
     * The method is the way an application asks for the password of a buyer
     * before changing it, so the check does not live in a controller.
     */
    public function verifyPassword(Customer $customer, string $password): bool
    {
        return Hash::check($password, (string) $customer->password);
    }

    /**
     * Mark the email address of a buyer as verified.
     */
    public function verifyEmail(Customer $customer): Customer
    {
        $customer->markEmailAsVerified();

        return $customer;
    }

    /**
     * Mark the email address as verified with the link that was sent.
     *
     * The application calls this method with the parameters of the link, so
     * it does not have to know how the link is built.
     */
    public function verifyEmailFromLink(int $id, string $hash): Customer
    {
        $customer = Customer::query()->find($id);

        if ($customer === null) {
            throw new SivarcartCoreException('El enlace de verificación no es válido.');
        }

        if (! hash_equals($customer->emailVerificationHash(), $hash)) {
            throw new SivarcartCoreException('El enlace de verificación no es válido.');
        }

        return $this->verifyEmail($customer);
    }

    /**
     * Give the buyer the orders it placed as a guest.
     *
     * The orders of a guest carry the email address of the buyer but no
     * account, so the history of the buyer is not lost when it signs up. The
     * method does nothing when there is no such order, and it can be called
     * more than once.
     */
    public function promoteGuest(Customer $customer): Customer
    {
        $email = $customer->email;

        if ($email === null || $email === '') {
            return $customer;
        }

        $orders = Order::query()
            ->whereNull('customer_id')
            ->where('customer_email', $email)
            ->get();

        foreach ($orders as $order) {
            $order->customer_id = $customer->getKey();
            $order->save();
        }

        $latest = Order::query()
            ->where('customer_id', $customer->getKey())
            ->latest('placed_at')
            ->first();

        if ($latest !== null && $latest->placed_at !== null) {
            $customer->last_order_at = $latest->placed_at;
            $customer->save();
        }

        return $customer;
    }

    /**
     * Write the moment of the last order of the buyer.
     */
    public function recordOrder(Customer $customer): Customer
    {
        $customer->last_order_at = now();
        $customer->save();

        return $customer;
    }

    /**
     * Sign a buyer in.
     *
     * The method returns null when the credentials do not match, so the
     * application can answer the same thing in both cases.
     */
    public function attempt(string $email, string $password): ?Customer
    {
        $guard = AuthFacade::guard($this->guard());

        if (! $guard->attempt(['email' => $this->normalizeEmail($email), 'password' => $password])) {
            return null;
        }

        $buyer = $guard->user();

        return $buyer instanceof Customer ? $buyer : null;
    }

    /**
     * Sign the buyer out.
     */
    public function logout(): void
    {
        AuthFacade::guard($this->guard())->logout();
    }

    /**
     * The buyer that is signed in right now, if there is one.
     */
    public function user(): ?Customer
    {
        $buyer = AuthFacade::guard($this->guard())->user();

        return $buyer instanceof Customer ? $buyer : null;
    }

    /**
     * Send the buyer the link that resets the password.
     *
     * The method returns false when there is no account with that email, so
     * the application can answer the same thing in both cases and not tell
     * who has an account.
     */
    public function sendPasswordResetLink(string $email): bool
    {
        $status = $this->broker()->sendResetLink([
            'email' => $this->normalizeEmail($email),
        ]);

        return $status === Password::RESET_LINK_SENT;
    }

    /**
     * Set the new password of a buyer with the token of a reset.
     */
    public function resetPassword(string $email, string $token, string $password): void
    {
        $status = $this->broker()->reset(
            [
                'email' => $this->normalizeEmail($email),
                'password' => $password,
                'password_confirmation' => $password,
                'token' => $token,
            ],
            function (CanResetPassword $buyer, string $password): void {
                $buyer->forceFill([
                    'password' => $password,
                    'remember_token' => Str::random(60),
                ])->save();
            },
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw new SivarcartCoreException('El enlace de restablecimiento no es válido o ya caducó.');
        }
    }

    /**
     * Save the address of a buyer.
     *
     * The address of the checkout is a plain array, so it is translated into
     * the row of the buyer here. The department of the address is resolved by
     * name or by code, because the checkout of the application names it the
     * way the buyer reads it.
     *
     * @param  array<string, mixed>  $address
     */
    public function saveAddress(Customer $customer, array $address): CustomerAddress
    {
        $saved = $customer->addresses()->create([
            'label' => $this->string($address, 'label'),
            'first_name' => $this->string($address, 'first_name') ?? $customer->first_name,
            'last_name' => $this->string($address, 'last_name') ?? $customer->last_name,
            'phone' => $this->string($address, 'phone') ?? $customer->phone,
            'address_line1' => $this->string($address, 'address_line1') ?? '',
            'address_line2' => $this->string($address, 'address_line2'),
            'department_id' => $this->department($address)?->getKey(),
            'municipality' => $this->string($address, 'municipality'),
            'city' => $this->string($address, 'city'),
            'reference' => $this->string($address, 'reference'),
            'postal_code' => $this->string($address, 'postal_code'),
            'latitude' => $address['latitude'] ?? null,
            'longitude' => $address['longitude'] ?? null,
            'is_default' => true,
            'is_billing' => (bool) ($address['is_billing'] ?? false),
        ]);

        $customer->addresses()
            ->whereKeyNot($saved->getKey())
            ->where('is_default', true)
            ->update(['is_default' => false]);

        return $saved;
    }

    /**
     * The address the buyer receives the orders in.
     */
    public function defaultAddress(Customer $customer): ?CustomerAddress
    {
        return $customer->addresses()
            ->orderByDesc('is_default')
            ->orderByDesc('id')
            ->first();
    }

    /**
     * The address of the buyer as the checkout reads it.
     *
     * @return array<string, mixed>
     */
    public function addressToArray(CustomerAddress $address): array
    {
        return [
            'label' => $address->label,
            'first_name' => $address->first_name,
            'last_name' => $address->last_name,
            'phone' => $address->phone,
            'address_line1' => $address->address_line1,
            'address_line2' => $address->address_line2,
            'department' => $address->department?->name,
            'municipality' => $address->municipality,
            'city' => $address->city,
            'reference' => $address->reference,
            'postal_code' => $address->postal_code,
        ];
    }

    /**
     * The guard the buyers sign in with.
     */
    private function guard(): string
    {
        $guard = config('sivarcart-core.buyers.guard', 'sivarcart');

        return is_string($guard) && $guard !== '' ? $guard : 'sivarcart';
    }

    /**
     * The broker that keeps the tokens of the password reset.
     */
    private function broker(): PasswordBroker
    {
        $broker = config('sivarcart-core.buyers.provider', 'sivarcart');

        return Password::broker(is_string($broker) && $broker !== '' ? $broker : 'sivarcart');
    }

    /**
     * The buyer with that email address, if there is one.
     */
    private function findByEmail(string $email): ?Customer
    {
        return Customer::query()->where('email', $email)->first();
    }

    /**
     * The email address of a buyer, ready to be compared.
     */
    private function normalizeEmail(string $email): string
    {
        return Str::lower(trim($email));
    }

    /**
     * The email address of a buyer, out of the attributes of a signup.
     *
     * @param  array<string, mixed>  $attributes
     */
    private function email(array $attributes): ?string
    {
        $email = $this->string($attributes, 'email');

        return $email === null ? null : $this->normalizeEmail($email);
    }

    /**
     * A value of the attributes, when it is a string with something in it.
     *
     * @param  array<string, mixed>  $attributes
     */
    private function string(array $attributes, string $key): ?string
    {
        $value = $attributes[$key] ?? null;

        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }

    /**
     * The type of the document of a buyer.
     *
     * @param  array<string, mixed>  $attributes
     */
    private function documentType(array $attributes): ?CustomerDocumentType
    {
        $type = $attributes['document_type'] ?? null;

        if ($type instanceof CustomerDocumentType) {
            return $type;
        }

        if (! is_string($type) || $type === '') {
            return null;
        }

        return CustomerDocumentType::tryFrom($type);
    }

    /**
     * Make sure the document of the buyer holds the format of its type.
     *
     * @param  array<string, mixed>  $attributes
     */
    private function assertDocumentIsValid(array $attributes): void
    {
        $type = $this->documentType($attributes);
        $number = $this->string($attributes, 'document_number');

        if ($type === null || $number === null) {
            return;
        }

        $reason = CustomerDocument::check($type, $number);

        if ($reason !== null) {
            throw InvalidDocument::with($type, $reason);
        }
    }

    /**
     * The department of an address.
     *
     * @param  array<string, mixed>  $address
     */
    private function department(array $address): ?Department
    {
        $id = $address['department_id'] ?? null;

        if (is_int($id) || (is_string($id) && $id !== '')) {
            return Department::query()->find($id);
        }

        $name = $this->string($address, 'department');

        if ($name === null) {
            return null;
        }

        return Department::query()
            ->where('name', $name)
            ->orWhere('code', $name)
            ->first();
    }
}
