<?php

declare(strict_types=1);

namespace Tarkontech\SivarCart\Models;

use Illuminate\Auth\Authenticatable as AuthenticatableTrait;
use Illuminate\Auth\MustVerifyEmail as MustVerifyEmailTrait;
use Illuminate\Auth\Passwords\CanResetPassword as CanResetPasswordTrait;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Contracts\Auth\MustVerifyEmail as MustVerifyEmailContract;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Tarkontech\SivarCart\Database\Factories\CustomerFactory;
use Tarkontech\SivarCart\Enums\CustomerDocumentType;
use Tarkontech\SivarCart\Models\Concerns\HasUuidColumn;
use Tarkontech\SivarCart\Notifications\AccountEmailVerificationNotification;
use Tarkontech\SivarCart\Notifications\AccountPasswordResetNotification;
use Tarkontech\SivarCart\Support\Auth;
use Throwable;

/**
 * A shopper.
 *
 * The shopper signs in with the guard of the package, so a buyer is a user of
 * the package and not of the application that installs it. The staff of the
 * store are a different thing: they are the users of the application, and the
 * package reads them through {@see Auth}.
 *
 * A customer without an email address or without a password is the buyer of an
 * order that was placed before the package asked for an account: it keeps the
 * history of those orders and can be promoted later.
 */
class Customer extends Model implements AuthenticatableContract, CanResetPasswordContract, MustVerifyEmailContract
{
    use AuthenticatableTrait;
    use CanResetPasswordTrait;
    use HasUuidColumn;
    use MustVerifyEmailTrait;
    use Notifiable;
    use SoftDeletes;

    /**
     * @var string
     */
    protected $table = 'sivarcart_customers';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'phone',
        'document_type',
        'document_number',
        'password',
        'accepts_marketing',
        'is_active',
        'notes',
        'meta',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'document_type' => CustomerDocumentType::class,
            'password' => 'hashed',
            'email_verified_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'accepts_marketing' => 'boolean',
            'is_active' => 'boolean',
            'last_order_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    /**
     * The addresses saved for this customer.
     *
     * @return HasMany<CustomerAddress, $this>
     */
    public function addresses(): HasMany
    {
        return $this->hasMany(CustomerAddress::class);
    }

    /**
     * The carts this customer owns.
     *
     * @return HasMany<Cart, $this>
     */
    public function carts(): HasMany
    {
        return $this->hasMany(Cart::class);
    }

    /**
     * The orders this customer placed.
     *
     * @return HasMany<Order, $this>
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * The hash that proves a link of verification belongs to this buyer.
     *
     * The hash is tied to the key of the application, so a link that was
     * written for one buyer does not verify another one.
     */
    public function emailVerificationHash(): string
    {
        return hash_hmac(
            'sha256',
            (string) $this->getEmailForVerification(),
            (string) config('app.key'),
        );
    }

    /**
     * Write to the buyer.
     *
     * A mailer that is down never breaks the account: the error goes to the
     * log and the account stays as it is. The same promise the notifications
     * of the order keep.
     */
    public function notifyBuyer(Notification $notification): void
    {
        try {
            $this->notify($notification);
        } catch (Throwable $exception) {
            Log::error('SivarCart could not send the notification to the buyer of the account.', [
                'notification' => $notification::class,
                'customer' => $this->uuid,
                'exception' => $exception->getMessage(),
            ]);
        }
    }

    /**
     * Send the buyer the link that resets the password.
     *
     * The notification of the package is used and not the one of the
     * framework, because the link points at the page the application declares
     * and not at the one Laravel expects by name.
     *
     * @param  string  $token
     */
    public function sendPasswordResetNotification($token): void
    {
        $this->notifyBuyer(new AccountPasswordResetNotification($this, $token));
    }

    /**
     * Send the buyer the link that verifies the email address.
     */
    public function sendEmailVerificationNotification(): void
    {
        $this->notifyBuyer(new AccountEmailVerificationNotification($this));
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): CustomerFactory
    {
        return CustomerFactory::new();
    }
}
