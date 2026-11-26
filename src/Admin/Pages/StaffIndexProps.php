<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Admin\Pages;

use Illuminate\Http\Request;
use SivarcartCore\SivarcartCore\Admin\AdminPage;
use SivarcartCore\SivarcartCore\Admin\AdminPageProps;
use SivarcartCore\SivarcartCore\Admin\AdminPresenter;
use SivarcartCore\SivarcartCore\Services\Admin\RoleService;
use SivarcartCore\SivarcartCore\Services\Admin\StaffService;
use SivarcartCore\SivarcartCore\Support\Auth;

/**
 * The data of the staff of the panel.
 *
 * The staff are the users of the application that hold a role of the store,
 * so the panel lists them and writes their roles.
 */
final readonly class StaffIndexProps implements AdminPageProps
{
    /**
     * @return array<string, mixed>
     */
    public function props(Request $request, AdminPage $page): array
    {
        $search = (string) $request->query('q', '');

        $staff = app(StaffService::class)->paginate(
            search: $search,
            perPage: (int) config('sivarcart-core.admin.per_page', 25),
        );

        $service = app(StaffService::class);

        $members = [];

        foreach ($staff->items() as $member) {
            $members[] = AdminPresenter::staffMember($member, $service->rolesOf($member));
        }

        $roles = [];

        foreach (app(RoleService::class)->all() as $role) {
            $roles[] = AdminPresenter::role($role);
        }

        return [
            'staff' => [
                'data' => $members,
                'meta' => [
                    'current_page' => $staff->currentPage(),
                    'last_page' => $staff->lastPage(),
                    'per_page' => $staff->perPage(),
                    'total' => $staff->total(),
                    'from' => $staff->firstItem(),
                    'to' => $staff->lastItem(),
                ],
            ],
            'roles' => $roles,
            'filters' => ['q' => $search],
            'canUpdate' => Auth::hasStorePermission(Auth::user(), 'staff.update'),
        ];
    }
}
