<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use SivarcartCore\SivarcartCore\Database\Factories\ProductVariantFactory;
use SivarcartCore\SivarcartCore\Models\Concerns\HasStoreMedia;
use SivarcartCore\SivarcartCore\Models\Concerns\HasUuidColumn;
use Spatie\MediaLibrary\HasMedia;

/**
 * A sellable unit of a product.
 *
 * This is where the price, the SKU and the stock live.
 */
class ProductVariant extends Model implements HasMedia
{
    use HasStoreMedia;
    use HasUuidColumn;
    use SoftDeletes;

    /**
     * @var string
     */
    protected $table = 'sivarcart_product_variants';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'product_id',
        'sku',
        'name',
        'price',
        'compare_at_price',
        'cost_price',
        'stock_quantity',
        'reserved_quantity',
        'low_stock_threshold',
        'weight',
        'barcode',
        'is_default',
        'is_active',
        'position',
        'image_path',
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
            'price' => 'decimal:2',
            'compare_at_price' => 'decimal:2',
            'cost_price' => 'decimal:2',
            'stock_quantity' => 'integer',
            'reserved_quantity' => 'integer',
            'low_stock_threshold' => 'integer',
            'weight' => 'decimal:3',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
            'position' => 'integer',
            'meta' => 'array',
        ];
    }

    /**
     * The media collections of the variant.
     *
     * The images of a variant belong to the product too, so the gallery of
     * the product holds them as well.
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('images');
    }

    /**
     * Limit the query to the variants that can be sold.
     *
     * @param  Builder<ProductVariant>  $query
     * @return Builder<ProductVariant>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * The product this variant belongs to.
     *
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * The option values that identify this variant.
     *
     * @return BelongsToMany<ProductOptionValue, $this>
     */
    public function optionValues(): BelongsToMany
    {
        return $this->belongsToMany(ProductOptionValue::class, 'sivarcart_product_variant_values');
    }

    /**
     * The images of this variant.
     *
     * @return HasMany<ProductImage, $this>
     */
    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class);
    }

    /**
     * The stock movements recorded for this variant.
     *
     * @return HasMany<InventoryMovement, $this>
     */
    public function inventoryMovements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class);
    }

    /**
     * The option values of this variant as a readable label.
     *
     * For example "Rojo / M".
     */
    public function optionLabel(): string
    {
        $values = $this->optionValues()
            ->orderBy('sivarcart_product_option_values.position')
            ->pluck('sivarcart_product_option_values.value');

        return $values->filter(fn (mixed $value): bool => is_string($value) && $value !== '')
            ->implode(' / ');
    }

    /**
     * The units available for sale.
     */
    public function availableStock(): int
    {
        return $this->stock_quantity - $this->reserved_quantity;
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): ProductVariantFactory
    {
        return ProductVariantFactory::new();
    }
}
