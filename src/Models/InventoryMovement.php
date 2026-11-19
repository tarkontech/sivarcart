<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use SivarcartCore\SivarcartCore\Database\Factories\InventoryMovementFactory;
use SivarcartCore\SivarcartCore\Enums\InventoryMovementType;

/**
 * A single entry of the stock ledger.
 *
 * Movements are append only: they explain how the stock of a variant reached
 * its current quantity.
 */
class InventoryMovement extends Model
{
    /**
     * The table does not keep a "updated_at" column.
     */
    public const ?string UPDATED_AT = null;

    /**
     * @var string
     */
    protected $table = 'sivarcart_inventory_movements';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'product_variant_id',
        'type',
        'quantity',
        'stock_after',
        'reason',
        'reference_type',
        'reference_id',
        'user_id',
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
            'type' => InventoryMovementType::class,
            'quantity' => 'integer',
            'stock_after' => 'integer',
            'reference_id' => 'integer',
            'meta' => 'array',
        ];
    }

    /**
     * The variant whose stock changed.
     *
     * @return BelongsTo<ProductVariant, $this>
     */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }

    /**
     * The member of the staff who recorded the movement.
     *
     * @return BelongsTo<StoreUser, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(StoreUser::class);
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): InventoryMovementFactory
    {
        return InventoryMovementFactory::new();
    }
}
