<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Admin\Pages;

use Illuminate\Http\Request;
use SivarcartCore\SivarcartCore\Admin\AdminPage;
use SivarcartCore\SivarcartCore\Admin\AdminPageProps;
use SivarcartCore\SivarcartCore\Admin\AdminPresenter;
use SivarcartCore\SivarcartCore\Models\StoreRole;

/**
 * The data of the roles of the staff of the store.
 *
 * The roles come from the seeder of the package, and the permissions they
 * grant are the ones the middleware of the permissions and the routes of the
 * panel check.
 */
final readonly class RolesIndexProps implements AdminPageProps
{
    /**
     * @return array<string, mixed>
     */
    public function props(Request $request, AdminPage $page): array
    {
        $roles = StoreRole::query()
            ->withCount('users')
            ->orderBy('name')
            ->get();

        $presented = [];

        foreach ($roles as $role) {
            $presented[] = AdminPresenter::role($role);
        }

        return ['roles' => $presented];
    }
}
