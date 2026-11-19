<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use SivarcartCore\SivarcartCore\Database\Factories\TaxFactory;

/**
 * A tax category and the rate it applies.
 *
 * El Salvador charges a 13% IVA, but a store also sells products that are
 * exempt or that are not taxed at all.
 */
class Tax extends Model
{
    /**
     * @var string
     */
    protected $table = 'sivarcart_taxes';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'code',
        'rate',
        'is_default',
        'is_active',
        'applies_to_shipping',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'rate' => 'decimal:4',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
            'applies_to_shipping' => 'boolean',
        ];
    }

    /**
     * The products taxed with this category.
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
    protected static function newFactory(): TaxFactory
    {
        return TaxFactory::new();
    }
}
