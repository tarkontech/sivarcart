<?php

declare(strict_types=1);

namespace Tarkontech\SivarCart\Admin\Pages;

use Illuminate\Http\Request;
use Tarkontech\SivarCart\Admin\AdminPage;
use Tarkontech\SivarCart\Admin\AdminPageProps;
use Tarkontech\SivarCart\Admin\AdminPermissions;
use Tarkontech\SivarCart\Admin\AdminPresenter;
use Tarkontech\SivarCart\Services\Admin\RoleService;
use Tarkontech\SivarCart\Support\Auth;

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
