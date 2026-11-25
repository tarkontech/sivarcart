<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use SivarcartCore\SivarcartCore\Database\Factories\ProductImageFactory;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * An image of a product or of one of its variants.
 *
 * The package stores the media with Spatie Media Library, but on its own
 * table: the model keeps the name and the prefix of the package, and the
 * media library takes care of the file and of the thumbnails.
 *
 * The columns of the domain stay here: they say which product the image
 * belongs to, which variant it is of, whether it is the main one and what
 * text describes it.
 */
class ProductImage extends Media
{
    /**
     * The model of the media comes from Spatie, and it does not know about the
     * factories of Laravel, so the package brings them back.
     *
     * @use HasFactory<Factory>
     */
    use HasFactory;

    /**
     * @var string
     */
    protected $table = 'sivarcart_product_images';

    /**
     * The columns that may be filled in mass.
     *
     * @var list<string>
     */
    protected $fillable = [
        'product_id',
        'product_variant_id',
        'path',
        'disk',
        'alt',
        'position',
        'is_primary',
        'model_type',
        'model_id',
        'uuid',
        'collection_name',
        'name',
        'file_name',
        'mime_type',
        'conversions_disk',
        'size',
        'manipulations',
        'custom_properties',
        'generated_conversions',
        'responsive_images',
    ];

    /**
     * The column that holds the order of the media is the one the domain
     * already had, so the images of a product do not need a second counter.
     *
     * @var array<string, string>
     */
    protected array $sortable = ['order_column_name' => 'position'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'is_primary' => 'boolean',
        ];
    }

    /**
     * The product this image belongs to.
     *
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * The variant this image is specific to.
     *
     * @return BelongsTo<ProductVariant, $this>
     */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    /**
     * The order of the last image of the gallery.
     *
     * The gallery of a product also holds the images of its variants, so the
     * order is counted over the whole gallery and not only over the images
     * the product owns.
     */
    public function getHighestOrderNumber(): int
    {
        if ($this->product_id === null) {
            return parent::getHighestOrderNumber();
        }

        return (int) static::query()
            ->where('product_id', $this->product_id)
            ->max($this->determineOrderColumnName());
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): ProductImageFactory
    {
        return ProductImageFactory::new();
    }
}
