<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Livewire\Admin\Roles;

use Illuminate\Contracts\View\View;
use Livewire\Component;
use SivarcartCore\SivarcartCore\Models\StoreRole;

/**
 * The roles of the staff of the store.
 *
 * The roles come from the seeder of the package, and the permissions they
 * grant are the ones the middleware of the permissions and the routes of the
 * panel check.
 */
class RoleList extends Component
{
    /**
     * Render the list.
     */
    public function render(): View
    {
        return view('sivarcart-core::admin.pages.roles.index', [
            'roles' => StoreRole::query()
                ->withCount('users')
                ->orderBy('name')
                ->get(),
        ]);
    }
}
