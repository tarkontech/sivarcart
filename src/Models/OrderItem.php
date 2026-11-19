<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use SivarcartCore\SivarcartCore\Database\Factories\OrderItemFactory;

/**
 * A line of an order.
 *
 * Everything the shopper bought is copied here, so the order stays readable
 * even when the product is renamed, repriced or removed from the catalog.
 */
class OrderItem extends Model
{
    /**
     * @var string
     */
    protected $table = 'sivarcart_order_items';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'order_id',
        'product_id',
        'product_variant_id',
        'name',
        'sku',
        'variant_options',
        'unit_price',
        'quantity',
        'line_subtotal',
        'discount_total',
        'tax_rate',
        'tax_total',
        'line_total',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'variant_options' => 'array',
            'unit_price' => 'decimal:2',
            'quantity' => 'integer',
            'line_subtotal' => 'decimal:2',
            'discount_total' => 'decimal:2',
            'tax_rate' => 'decimal:4',
            'tax_total' => 'decimal:2',
            'line_total' => 'decimal:2',
        ];
    }

    /**
     * The order this line belongs to.
     *
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * The product this line refers to.
     *
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * The variant this line refers to.
     *
     * @return BelongsTo<ProductVariant, $this>
     */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): OrderItemFactory
    {
        return OrderItemFactory::new();
    }
}
