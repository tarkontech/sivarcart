<?php

declare(strict_types=1);

namespace Tarkontech\SivarCart\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Tarkontech\SivarCart\Database\Factories\CartItemFactory;

/**
 * A line of a shopping cart.
 *
 * The name, the SKU, the options and the price are snapshots, so a cart keeps
 * showing what the shopper picked even when the catalog changes afterwards.
 */
class CartItem extends Model
{
    /**
     * @var string
     */
    protected $table = 'sivarcart_cart_items';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'cart_id',
        'product_id',
        'product_variant_id',
        'name',
        'sku',
        'options',
        'unit_price',
        'quantity',
        'line_subtotal',
        'discount_total',
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
            'options' => 'array',
            'unit_price' => 'decimal:2',
            'quantity' => 'integer',
            'line_subtotal' => 'decimal:2',
            'discount_total' => 'decimal:2',
            'tax_total' => 'decimal:2',
            'line_total' => 'decimal:2',
        ];
    }

    /**
     * The cart this line belongs to.
     *
     * @return BelongsTo<Cart, $this>
     */
    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
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
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): CartItemFactory
    {
        return CartItemFactory::new();
    }
}
