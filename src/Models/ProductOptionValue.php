<?php

declare(strict_types=1);

namespace Tarkontech\SivarCart\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Tarkontech\SivarCart\Database\Factories\ProductOptionValueFactory;

/**
 * A value an option accepts, such as "M" or "Rojo".
 */
class ProductOptionValue extends Model
{
    /**
     * @var string
     */
    protected $table = 'sivarcart_product_option_values';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'product_option_id',
        'value',
        'label',
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
            'position' => 'integer',
            'meta' => 'array',
        ];
    }

    /**
     * The option this value belongs to.
     *
     * @return BelongsTo<ProductOption, $this>
     */
    public function option(): BelongsTo
    {
        return $this->belongsTo(ProductOption::class);
    }

    /**
     * The variants that use this value.
     *
     * @return BelongsToMany<ProductVariant, $this>
     */
    public function variants(): BelongsToMany
    {
        return $this->belongsToMany(ProductVariant::class, 'sivarcart_product_variant_values');
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): ProductOptionValueFactory
    {
        return ProductOptionValueFactory::new();
    }
}
