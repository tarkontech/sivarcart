<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Admin\Pages;

use Illuminate\Http\Request;
use SivarcartCore\SivarcartCore\Admin\AdminPage;
use SivarcartCore\SivarcartCore\Admin\AdminPageProps;
use SivarcartCore\SivarcartCore\Admin\AdminPermissions;
use SivarcartCore\SivarcartCore\Admin\AdminPresenter;
use SivarcartCore\SivarcartCore\Services\Admin\RoleService;
use SivarcartCore\SivarcartCore\Support\Auth;

/**
 * The data of the roles of the staff of the store.
 *
 * The roles come from the seeder of the package, and the permissions they
 * grant are the ones the middleware of the permissions and the routes of the
 * panel check. The panel offers the whole vocabulary of the store, so the
 * staff can decide what every role grants.
 */
final readonly class RolesIndexProps implements AdminPageProps
{
    /**
     * @return array<string, mixed>
     */
    public function props(Request $request, AdminPage $page): array
    {
        $presented = [];

        foreach (app(RoleService::class)->all() as $role) {
            $presented[] = AdminPresenter::role($role);
        }

        return [
            'roles' => $presented,
            'permissions' => AdminPermissions::all(),
            'canUpdate' => Auth::hasStorePermission(Auth::user(), 'roles.update'),
        ];
    }
}
