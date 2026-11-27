<?php

declare(strict_types=1);

namespace Tarkontech\SivarCart\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Tarkontech\SivarCart\Database\Factories\ProductFactory;
use Tarkontech\SivarCart\Enums\ProductStatus;
use Tarkontech\SivarCart\Enums\ProductType;
use Tarkontech\SivarCart\Models\Concerns\HasStoreMedia;
use Tarkontech\SivarCart\Models\Concerns\HasUuidColumn;

/**
 * A product of the catalog.
 *
 * The price and the stock live in the variants, never here, so quoting,
 * discounting and stock movements always run through a single code path. A
 * product always owns at least one variant: a simple product owns exactly one
 * variant without option values.
 */
class Product extends Model implements HasMedia
{
    use HasStoreMedia;
    use HasUuidColumn;
    use SoftDeletes;

    /**
     * @var string
     */
    protected $table = 'sivarcart_products';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'type',
        'name',
        'slug',
        'short_description',
        'description',
        'status',
        'is_featured',
        'is_virtual',
        'tax_id',
        'brand_id',
        'thumbnail_path',
        'weight',
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
            'type' => ProductType::class,
            'status' => ProductStatus::class,
            'is_featured' => 'boolean',
            'is_virtual' => 'boolean',
            'weight' => 'decimal:3',
            'published_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    /**
     * Bootstrap the model.
     *
     * A simple product always needs a sellable unit, so one is created when
     * the product is created without any variant.
     */
    protected static function booted(): void
    {
        static::created(function (self $product): void {
            if ($product->type !== ProductType::SIMPLE) {
                return;
            }

            if ($product->variants()->exists()) {
                return;
            }

            $product->variants()->create([
                'name' => $product->name,
                'price' => 0,
                'stock_quantity' => 0,
                'is_default' => true,
                'is_active' => true,
            ]);
        });
    }

    /**
     * The media collections of the product.
     *
     * The images of the product live in the collection "images", and so do
     * the images of its variants: the gallery of a product is the one the
     * shopper sees.
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('images');
    }

    /**
     * Limit the query to published products.
     *
     * @param  Builder<Product>  $query
     * @return Builder<Product>
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', ProductStatus::PUBLISHED->value);
    }

    /**
     * The sellable units of this product.
     *
     * @return HasMany<ProductVariant, $this>
     */
    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    /**
     * The options this product offers.
     *
     * @return HasMany<ProductOption, $this>
     */
    public function options(): HasMany
    {
        return $this->hasMany(ProductOption::class);
    }

    /**
     * Every value the options of this product accept.
     *
     * @return HasManyThrough<ProductOptionValue, ProductOption, $this>
     */
    public function optionValues(): HasManyThrough
    {
        return $this->hasManyThrough(
            ProductOptionValue::class,
            ProductOption::class,
            'product_id',
            'product_option_id',
        );
    }

    /**
     * The images of this product.
     *
     * @return HasMany<ProductImage, $this>
     */
    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class);
    }

    /**
     * The categories this product is filed under.
     *
     * @return BelongsToMany<Category, $this>
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'sivarcart_category_product');
    }

    /**
     * The brand of this product.
     *
     * @return BelongsTo<Brand, $this>
     */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    /**
     * The tax category of this product.
     *
     * @return BelongsTo<Tax, $this>
     */
    public function tax(): BelongsTo
    {
        return $this->belongsTo(Tax::class);
    }

    /**
     * The stock movements recorded for the variants of this product.
     *
     * @return HasManyThrough<InventoryMovement, ProductVariant, $this>
     */
    public function inventoryMovements(): HasManyThrough
    {
        return $this->hasManyThrough(
            InventoryMovement::class,
            ProductVariant::class,
            'product_id',
            'product_variant_id',
        );
    }

    /**
     * The variant shown when the shopper does not pick one.
     */
    public function defaultVariant(): ?ProductVariant
    {
        return $this->variants()
            ->orderByDesc('is_default')
            ->orderBy('id')
            ->first();
    }

    /**
     * The SKU of the default variant.
     */
    public function sku(): ?string
    {
        return $this->defaultVariant()?->sku;
    }

    /**
     * The lowest and the highest price among the active variants.
     *
     * @return array{min: float, max: float}|null
     */
    public function priceRange(): ?array
    {
        $variants = $this->variants()->active()->get();

        if ($variants->isEmpty()) {
            return null;
        }

        $prices = $variants->map(fn (ProductVariant $variant): float => (float) $variant->price);

        $min = $prices->min();
        $max = $prices->max();

        if ($min === null || $max === null) {
            return null;
        }

        return ['min' => $min, 'max' => $max];
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): ProductFactory
    {
        return ProductFactory::new();
    }
}
