<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use SivarcartCore\SivarcartCore\Database\Factories\CustomerFactory;
use SivarcartCore\SivarcartCore\Enums\CustomerDocumentType;
use SivarcartCore\SivarcartCore\Models\Concerns\HasUuidColumn;

/**
 * A shopper.
 *
 * A customer may be created without an email address or a password, which is
 * what makes guest checkout possible.
 */
class Customer extends Model
{
    use HasUuidColumn;
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
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): CustomerFactory
    {
        return CustomerFactory::new();
    }
}
