<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use SivarcartCore\SivarcartCore\Database\Factories\BrandFactory;
use SivarcartCore\SivarcartCore\Models\Concerns\HasStoreMedia;
use Spatie\MediaLibrary\HasMedia;

/**
 * A brand of the catalog.
 */
class Brand extends Model implements HasMedia
{
    use HasStoreMedia;

    /**
     * @var string
     */
    protected $table = 'sivarcart_brands';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'slug',
        'description',
        'logo_path',
        'is_active',
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
            'is_active' => 'boolean',
            'position' => 'integer',
        ];
    }

    /**
     * The media collections of the brand.
     *
     * The logo of the brand lives in the collection "logo", and it is
     * always one file: the new one replaces the old one.
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('logo')->singleFile();
    }

    /**
     * The products of this brand.
     *
     * @return HasMany<Product, $this>
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): BrandFactory
    {
        return BrandFactory::new();
    }
}
