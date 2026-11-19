<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use SivarcartCore\SivarcartCore\Database\Factories\StoreRoleFactory;

/**
 * A role of the store staff and the permissions it grants.
 */
class StoreRole extends Model
{
    /**
     * @var string
     */
    protected $table = 'sivarcart_store_roles';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'slug',
        'description',
        'permissions',
        'is_system',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'permissions' => 'array',
            'is_system' => 'boolean',
        ];
    }

    /**
     * The members of the staff holding this role.
     *
     * @return BelongsToMany<StoreUser, $this>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(StoreUser::class, 'sivarcart_store_role_user');
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): StoreRoleFactory
    {
        return StoreRoleFactory::new();
    }
}
