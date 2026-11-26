<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Services\Admin;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Pagination\LengthAwarePaginator;
use SivarcartCore\SivarcartCore\Models\StoreRole;
use SivarcartCore\SivarcartCore\Support\Auth;

/**
 * The staff of the store, as the panel sees them.
 *
 * The staff are the users of the host application, so the package does not
 * own them: it reads the model the application names and it writes the roles
 * of the store on it.
 *
 * The panel never deletes a user of the application. "Dar de baja" a member
 * of the staff takes the roles of the store away from them, so they stop
 * seeing the panel and the application keeps its user.
 */
final class StaffService
{
    /**
     * The pivot that joins the staff with the roles of the store.
     */
    private const string PIVOT = 'sivarcart_store_role_user';

    /**
     * The members of the staff of the store.
     *
     * The list holds the users that hold at least one role of the store: the
     * ones without a role cannot see the panel, so they are not staff yet.
     *
     * @return LengthAwarePaginator<int, Model&Authenticatable>
     */
    public function paginate(string $search = '', int $perPage = 25): LengthAwarePaginator
    {
        $model = Auth::newModel();

        $table = $model->getTable();
        $key = $model->getKeyName();

        return $model->newQuery()
            ->whereExists(function (QueryBuilder $query) use ($table, $key): void {
                $query->selectRaw('1')
                    ->from(self::PIVOT)
                    ->whereColumn(self::PIVOT.'.user_id', $table.'.'.$key);
            })
            ->when($search !== '', function (Builder $query) use ($search, $table): void {
                $query->where(function (Builder $query) use ($search, $table): void {
                    $query->where($table.'.name', 'like', '%'.$search.'%')
                        ->orWhere($table.'.email', 'like', '%'.$search.'%');
                });
            })
            ->when(method_exists($model, 'storeRoles'), fn (Builder $query): Builder => $query->with('storeRoles'))
            ->orderBy($table.'.'.$key)
            ->paginate($perPage);
    }

    /**
     * One member of the staff, by its identifier.
     */
    public function find(int $id): ?Model
    {
        $model = Auth::newModel();

        $found = $model->newQuery()->find($id);

        if (! $found instanceof Model) {
            return null;
        }

        if (method_exists($found, 'storeRoles')) {
            $found->load('storeRoles');
        }

        return $found;
    }

    /**
     * Give a member of the staff a role of the store.
     *
     * @param  list<string>  $slugs
     */
    public function assignRoles(Model $user, array $slugs): void
    {
        if (! method_exists($user, 'assignStoreRole')) {
            return;
        }

        foreach ($slugs as $slug) {
            $role = StoreRole::query()->where('slug', $slug)->first();

            if ($role instanceof StoreRole) {
                $user->assignStoreRole($role);
            }
        }
    }

    /**
     * Take a role of the store away from a member of the staff.
     */
    public function revokeRole(Model $user, string $slug): void
    {
        if (! method_exists($user, 'revokeStoreRole')) {
            return;
        }

        $user->revokeStoreRole($slug);
    }

    /**
     * Set the roles of the store of a member of the staff.
     *
     * The roles that are not in the list are taken away, so the panel writes
     * the whole set of roles of the person and not one of them at a time.
     *
     * @param  list<string>  $slugs
     */
    public function setRoles(Model $user, array $slugs): void
    {
        if (! method_exists($user, 'storeRoles')) {
            return;
        }

        $ids = [];

        foreach ($slugs as $slug) {
            $id = StoreRole::query()->where('slug', $slug)->value('id');

            if ($id !== null) {
                $ids[] = (int) $id;
            }
        }

        $user->storeRoles()->sync($ids);
    }

    /**
     * Take every role of the store away from a member of the staff.
     *
     * This is what "dar de baja" means in the panel: the person stops seeing
     * the panel, and the application keeps its user.
     */
    public function removeRoles(Model $user): void
    {
        if (! method_exists($user, 'storeRoles')) {
            return;
        }

        $user->storeRoles()->detach();
    }

    /**
     * The roles of the store granted to a member of the staff.
     *
     * @return list<string>
     */
    public function rolesOf(Model $user): array
    {
        if (! method_exists($user, 'storeRoles')) {
            return [];
        }

        $slugs = [];

        foreach ($user->storeRoles()->get() as $role) {
            $slugs[] = (string) $role->slug;
        }

        return $slugs;
    }

    /**
     * The member of the staff that is signed in.
     */
    public function current(): ?Model
    {
        $user = Auth::user();

        return $user instanceof Model ? $user : null;
    }

    /**
     * Whether the user holds the trait of the roles of the store.
     */
    public function supportsRoles(Model $user): bool
    {
        return $user instanceof Authenticatable && method_exists($user, 'assignStoreRole');
    }
}
