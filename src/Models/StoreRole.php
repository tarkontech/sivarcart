<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use SivarcartCore\SivarcartCore\Database\Factories\StoreRoleFactory;
use SivarcartCore\SivarcartCore\Support\Auth;

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
     * Whether the role grants a permission.
     *
     * A permission is granted when the role lists it, when the role lists the
     * group it belongs to with a trailing "*", or when the role lists "*".
     */
    public function hasPermission(string $permission): bool
    {
        foreach ($this->permissions ?? [] as $granted) {
            if (! is_string($granted)) {
                continue;
            }

            if ($granted === '*' || $granted === $permission) {
                return true;
            }

            if (str_ends_with($granted, '.*') && str_starts_with($permission, substr($granted, 0, -1))) {
                return true;
            }
        }

        return false;
    }

    /**
     * The members of the staff holding this role.
     *
     * The staff are the users of the host application, so the relation
     * points at the model of the "users" provider of "config/auth.php".
     *
     * @return BelongsToMany<\Illuminate\Database\Eloquent\Model, $this>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(
            Auth::model(),
            'sivarcart_store_role_user',
            'store_role_id',
            'user_id',
        );
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): StoreRoleFactory
    {
        return StoreRoleFactory::new();
    }
}
