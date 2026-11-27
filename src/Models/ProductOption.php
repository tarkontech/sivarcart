<?php

declare(strict_types=1);

namespace Tarkontech\SivarCart\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Tarkontech\SivarCart\Database\Factories\ProductOptionFactory;
use Tarkontech\SivarCart\Enums\ProductOptionType;

/**
 * An option a product offers, such as "Talla" or "Color".
 */
class ProductOption extends Model
{
    /**
     * @var string
     */
    protected $table = 'sivarcart_product_options';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'product_id',
        'name',
        'type',
        'position',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => ProductOptionType::class,
            'position' => 'integer',
        ];
    }

    /**
     * The product this option belongs to.
     *
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * The values this option accepts.
     *
     * @return HasMany<ProductOptionValue, $this>
     */
    public function values(): HasMany
    {
        return $this->hasMany(ProductOptionValue::class);
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): ProductOptionFactory
    {
        return ProductOptionFactory::new();
    }
}
