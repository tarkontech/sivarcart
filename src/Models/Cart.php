<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use SivarcartCore\SivarcartCore\Database\Factories\CartFactory;
use SivarcartCore\SivarcartCore\Models\Concerns\HasUuidColumn;

/**
 * A shopping cart.
 *
 * The totals stored here are a cache of the derived totals. They are never
 * trusted on their own: they are recalculated from the items.
 */
class Cart extends Model
{
    use HasUuidColumn;

    /**
     * @var string
     */
    protected $table = 'sivarcart_carts';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'customer_id',
        'currency',
        'coupon_id',
        'subtotal',
        'discount_total',
        'tax_total',
        'shipping_total',
        'total',
        'item_count',
        'expires_at',
        'converted_at',
        'meta',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'discount_total' => 'decimal:2',
            'tax_total' => 'decimal:2',
            'shipping_total' => 'decimal:2',
            'total' => 'decimal:2',
            'item_count' => 'integer',
            'expires_at' => 'datetime',
            'converted_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    /**
     * The customer this cart belongs to.
     *
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * The items of this cart.
     *
     * @return HasMany<CartItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    /**
     * The coupon applied to this cart.
     *
     * @return BelongsTo<Coupon, $this>
     */
    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    /**
     * Whether this cart has already been turned into an order.
     */
    public function isConverted(): bool
    {
        return $this->converted_at !== null;
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): CartFactory
    {
        return CartFactory::new();
    }
}
