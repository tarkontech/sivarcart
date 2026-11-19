<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use SivarcartCore\SivarcartCore\Database\Factories\StoreUserFactory;
use SivarcartCore\SivarcartCore\Models\Concerns\HasUuidColumn;

/**
 * A member of the store staff.
 *
 * The package owns its own staff accounts instead of relying on the "users"
 * table of the host application, so a store can be installed on any Laravel
 * application without borrowing its authentication.
 */
class StoreUser extends Model
{
    use HasUuidColumn;
    use SoftDeletes;

    /**
     * @var string
     */
    protected $table = 'sivarcart_store_users';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'avatar_path',
        'is_active',
        'meta',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'last_login_at' => 'datetime',
            'is_active' => 'boolean',
            'meta' => 'array',
        ];
    }

    /**
     * The roles granted to this member of the staff.
     *
     * @return BelongsToMany<StoreRole, $this>
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(StoreRole::class, 'sivarcart_store_role_user');
    }

    /**
     * The stock movements this member of the staff recorded.
     *
     * @return HasMany<InventoryMovement, $this>
     */
    public function inventoryMovements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class);
    }

    /**
     * The order status changes this member of the staff recorded.
     *
     * @return HasMany<OrderStatusHistory, $this>
     */
    public function orderStatusHistories(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class);
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): StoreUserFactory
    {
        return StoreUserFactory::new();
    }
}
