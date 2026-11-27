<?php

declare(strict_types=1);

namespace Tarkontech\SivarCart\Services\Admin;

use Tarkontech\SivarCart\Admin\AdminPermissions;
use Tarkontech\SivarCart\Models\StoreRole;
use Tarkontech\SivarCart\Support\Slug;

/**
 * The roles of the staff of the store, as the panel sees them.
 *
 * A role is a name and the permissions it grants. The permissions are the
 * vocabulary of the store, so the panel offers the ones that exist and not
 * the ones the staff invents.
 */
final class RoleService
{
    /**
     * The roles of the store.
     *
     * @return list<StoreRole>
     */
    public function all(): array
    {
        return array_values(
            StoreRole::query()
                ->withCount('users')
                ->orderBy('name')
                ->get()
                ->all(),
        );
    }

    /**
     * One role of the store, by its slug.
     */
    public function find(string $slug): ?StoreRole
    {
        return StoreRole::query()->where('slug', $slug)->first();
    }

    /**
     * Create a role of the store.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): StoreRole
    {
        $role = new StoreRole;

        $role->fill($this->attributes($attributes, null));
        $role->save();

        return $role->refresh();
    }

    /**
     * Write a role of the store.
     *
     * The slug of a role that the package brought is not changed: the staff
     * that hold it would lose it.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function update(StoreRole $role, array $attributes): StoreRole
    {
        $role->fill($this->attributes($attributes, $role));
        $role->save();

        return $role->refresh();
    }

    /**
     * Remove a role of the store.
     *
     * The roles that the package brought are not removed: they are the ones
     * the panel and the command of the installation expect to find.
     */
    public function delete(StoreRole $role): void
    {
        if ($role->is_system) {
            return;
        }

        $role->users()->detach();

        $role->delete();
    }

    /**
     * The attributes of a role, ready to be filled.
     *
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function attributes(array $attributes, ?StoreRole $role): array
    {
        $name = trim((string) ($attributes['name'] ?? ''));

        $slug = trim((string) ($attributes['slug'] ?? ''));

        $isSystem = $role instanceof StoreRole && $role->is_system;

        if ($isSystem) {
            $slug = $role->slug;
        } elseif ($slug === '') {
            $slug = Slug::unique($name, 'sivarcart_store_roles');
        } else {
            $slug = Slug::unique($slug, 'sivarcart_store_roles', $role?->getKey());
        }

        return [
            'name' => $name,
            'slug' => $slug,
            'description' => $this->text($attributes['description'] ?? null),
            'permissions' => $this->permissionsOf($role, $attributes['permissions'] ?? null),
            'is_system' => $isSystem,
        ];
    }

    /**
     * The permissions a role grants after the staff wrote them.
     *
     * The panel writes the permissions of the vocabulary of the store one by
     * one, but a role can also hold a wildcard: "*" grants everything and
     * "catalog.*" grants the whole group of the catalog. A wildcard that the
     * staff did not touch is kept, so saving a role does not quietly take
     * away a permission that the wildcard was granting.
     *
     * @return list<string>
     */
    private function permissionsOf(?StoreRole $role, mixed $value): array
    {
        $written = $this->permissions($value);

        $kept = [];

        foreach ($this->wildcardsOf($role) as $granted) {
            if ($this->wildcardSurvives($granted, $written)) {
                $kept[] = $granted;
            }
        }

        $implied = [];

        foreach ($kept as $granted) {
            foreach ($this->impliedBy($granted) as $permission) {
                $implied[] = $permission;
            }
        }

        $permissions = [];

        foreach ($written as $permission) {
            if (! in_array($permission, $implied, true)) {
                $permissions[] = $permission;
            }
        }

        return array_values(array_unique([...$kept, ...$permissions]));
    }

    /**
     * The wildcards that a role holds.
     *
     * @return list<string>
     */
    private function wildcardsOf(?StoreRole $role): array
    {
        if (! $role instanceof StoreRole) {
            return [];
        }

        $wildcards = [];

        foreach ($role->permissions ?? [] as $granted) {
            if (is_string($granted) && str_ends_with($granted, '*')) {
                $wildcards[] = $granted;
            }
        }

        return $wildcards;
    }

    /**
     * Whether a wildcard still grants everything it granted.
     *
     * @param  list<string>  $written
     */
    private function wildcardSurvives(string $wildcard, array $written): bool
    {
        $implied = $this->impliedBy($wildcard);

        if ($implied === []) {
            return false;
        }

        foreach ($implied as $permission) {
            if (! in_array($permission, $written, true)) {
                return false;
            }
        }

        return true;
    }

    /**
     * The permissions of the vocabulary that a wildcard grants.
     *
     * @return list<string>
     */
    private function impliedBy(string $wildcard): array
    {
        $implied = [];

        foreach (AdminPermissions::all() as $permission) {
            if ($wildcard === '*') {
                $implied[] = $permission;

                continue;
            }

            if (str_starts_with($permission, substr($wildcard, 0, -1))) {
                $implied[] = $permission;
            }
        }

        return $implied;
    }

    /**
     * The permissions a role grants.
     *
     * @return list<string>
     */
    private function permissions(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        $permissions = [];

        foreach ($value as $permission) {
            if (is_string($permission) && trim($permission) !== '') {
                $permissions[] = trim($permission);
            }
        }

        return array_values(array_unique($permissions));
    }

    /**
     * A text that is not an empty string.
     */
    private function text(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }
}
