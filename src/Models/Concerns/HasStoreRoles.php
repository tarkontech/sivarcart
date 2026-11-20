<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Models\Concerns;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use SivarcartCore\SivarcartCore\Models\StoreRole;

/**
 * Gives the user model of the host application the roles of the store.
 *
 * The package does not own the users, so it cannot add this trait by
 * itself: the application adds it to its own model.
 *
 * ```php
 * class User extends Authenticatable
 * {
 *     use HasStoreRoles;
 * }
 * ```
 */
trait HasStoreRoles
{
    /**
     * The roles of the store granted to this user.
     *
     * @return BelongsToMany<StoreRole, $this>
     */
    public function storeRoles(): BelongsToMany
    {
        return $this->belongsToMany(
            StoreRole::class,
            'sivarcart_store_role_user',
            'user_id',
            'store_role_id',
        );
    }

    /**
     * Whether the user holds a role of the store.
     */
    public function hasStoreRole(string $slug): bool
    {
        return $this->storeRoles()->where('slug', $slug)->exists();
    }

    /**
     * Whether one of the roles of the user grants a permission.
     */
    public function hasStorePermission(string $permission): bool
    {
        return $this->storeRoles->contains(
            fn (StoreRole $role): bool => $role->hasPermission($permission),
        );
    }

    /**
     * Grant roles of the store to the user.
     */
    public function assignStoreRole(StoreRole|string ...$roles): void
    {
        $this->storeRoles()->syncWithoutDetaching($this->resolveStoreRoleIds($roles));
    }

    /**
     * Take a role of the store away from the user.
     */
    public function revokeStoreRole(StoreRole|string $role): void
    {
        $id = $role instanceof StoreRole
            ? $role->getKey()
            : StoreRole::query()->where('slug', $role)->value('id');

        if ($id === null) {
            return;
        }

        $this->storeRoles()->detach($id);
    }

    /**
     * Turn the roles into the keys the pivot table stores.
     *
     * @param  array<int|string, StoreRole|string>  $roles
     * @return array<int, mixed>
     */
    private function resolveStoreRoleIds(array $roles): array
    {
        $ids = [];

        foreach ($roles as $role) {
            $id = $role instanceof StoreRole
                ? $role->getKey()
                : StoreRole::query()->where('slug', $role)->value('id');

            if ($id !== null) {
                $ids[] = $id;
            }
        }

        return $ids;
    }
}
