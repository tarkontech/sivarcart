<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use SivarcartCore\SivarcartCore\Database\Factories\StoreFactory;
use SivarcartCore\SivarcartCore\Models\Concerns\HasStoreMedia;
use SivarcartCore\SivarcartCore\Models\Concerns\HasUuidColumn;
use Spatie\MediaLibrary\HasMedia;

/**
 * The profile and the settings of the single store this installation serves.
 */
class Store extends Model implements HasMedia
{
    use HasStoreMedia;
    use HasUuidColumn;

    /**
     * @var string
     */
    protected $table = 'sivarcart_stores';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'slug',
        'legal_name',
        'tax_id',
        'email',
        'phone',
        'whatsapp',
        'address_line1',
        'address_line2',
        'department_id',
        'municipality',
        'city',
        'postal_code',
        'country_code',
        'phone_country_code',
        'currency',
        'timezone',
        'locale',
        'logo_path',
        'favicon_path',
        'prices_include_tax',
        'default_tax_rate',
        'low_stock_threshold',
        'order_number_prefix',
        'payment_number_prefix',
        'meta',
        'is_active',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'prices_include_tax' => 'boolean',
            'default_tax_rate' => 'decimal:4',
            'meta' => 'array',
            'is_active' => 'boolean',
        ];
    }

    /**
     * The media collections of the store.
     *
     * The logo and the favicon of the store live in their own collections,
     * and each one of them is always a single file.
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('logo')->singleFile();
        $this->addMediaCollection('favicon')->singleFile();
    }

    /**
     * The department the store is located in.
     *
     * @return BelongsTo<Department, $this>
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): StoreFactory
    {
        return StoreFactory::new();
    }
}
