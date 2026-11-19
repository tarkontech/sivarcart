<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Models;

use SivarcartCore\SivarcartCore\Database\Factories\ShippingMethodFactory;
use SivarcartCore\SivarcartCore\Enums\ShippingMethodType;
use SivarcartCore\SivarcartCore\Models\Concerns\HasUuidColumn;

/**
 * A way of delivering an order.
 *
 * Shipping zones and rates per department belong to an optional module; the
 * core only keeps the method the shopper chose and what it cost.
 */
class ShippingMethod extends Model
{
    use HasUuidColumn;

    /**
     * @var string
     */
    protected $table = 'sivarcart_shipping_methods';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'code',
        'name',
        'type',
        'price',
        'min_order_amount',
        'max_order_amount',
        'estimated_days',
        'instructions',
        'is_active',
        'position',
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
            'type' => ShippingMethodType::class,
            'price' => 'decimal:2',
            'min_order_amount' => 'decimal:2',
            'max_order_amount' => 'decimal:2',
            'estimated_days' => 'integer',
            'is_active' => 'boolean',
            'position' => 'integer',
            'meta' => 'array',
        ];
    }

    /**
     * Whether this method can be used for an order of the given amount.
     */
    public function appliesTo(float $orderAmount): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if ($this->min_order_amount !== null && $orderAmount < (float) $this->min_order_amount) {
            return false;
        }

        if ($this->max_order_amount !== null && $orderAmount > (float) $this->max_order_amount) {
            return false;
        }

        return true;
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): ShippingMethodFactory
    {
        return ShippingMethodFactory::new();
    }
}
