<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use SivarcartCore\SivarcartCore\Database\Factories\PaymentMethodFactory;
use SivarcartCore\SivarcartCore\Enums\PaymentMethodType;
use SivarcartCore\SivarcartCore\Models\Concerns\HasUuidColumn;

/**
 * A way of paying for an order.
 *
 * Online methods ("wompi") and offline methods ("cash_on_delivery",
 * "bank_transfer") share the same table: only the online ones carry a driver
 * and credentials.
 */
class PaymentMethod extends Model
{
    use HasUuidColumn;
    use SoftDeletes;

    /**
     * @var string
     */
    protected $table = 'sivarcart_payment_methods';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'code',
        'name',
        'type',
        'driver',
        'credentials',
        'instructions',
        'icon',
        'is_active',
        'position',
        'meta',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'credentials',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => PaymentMethodType::class,
            'credentials' => 'encrypted:array',
            'is_active' => 'boolean',
            'position' => 'integer',
            'meta' => 'array',
        ];
    }

    /**
     * The payments collected with this method.
     *
     * @return HasMany<Payment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Whether this method is collected through a gateway.
     */
    public function isOnline(): bool
    {
        return $this->type === PaymentMethodType::ONLINE;
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): PaymentMethodFactory
    {
        return PaymentMethodFactory::new();
    }
}
