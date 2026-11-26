<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Http\Controllers\Admin;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use SivarcartCore\SivarcartCore\Services\Admin\StaffService;
use SivarcartCore\SivarcartCore\Support\Auth;

/**
 * Writes the staff of the store.
 *
 * The staff are the users of the host application, so the panel does not own
 * them: it creates them the same way the command of the installation does,
 * and it writes the roles of the store on them.
 *
 * "Dar de baja" a member of the staff takes the roles of the store away from
 * them, so they stop seeing the panel. The application keeps its user.
 */
final class StaffController
{
    /**
     * Give a member of the staff its roles of the store.
     */
    public function update(Request $request, int $staff): RedirectResponse
    {
        $model = app(StaffService::class)->find($staff);

        if (! $model instanceof Model) {
            abort(404, __('sivarcart-core::admin.staff.not_found'));
        }

        $attributes = $request->validate([
            'roles' => ['array'],
            'roles.*' => ['string', 'exists:sivarcart_store_roles,slug'],
        ]);

        app(StaffService::class)->setRoles($model, $attributes['roles'] ?? []);

        Inertia::flash('notice', __('sivarcart-core::admin.staff.saved'));

        return back()->setStatusCode(303);
    }

    /**
     * Take the roles of the store away from a member of the staff.
     */
    public function destroy(int $staff): RedirectResponse
    {
        $model = app(StaffService::class)->find($staff);

        if (! $model instanceof Model) {
            abort(404, __('sivarcart-core::admin.staff.not_found'));
        }

        app(StaffService::class)->removeRoles($model);

        Inertia::flash('notice', __('sivarcart-core::admin.staff.removed'));

        return back()->setStatusCode(303);
    }

    /**
     * Create a member of the staff of the store.
     *
     * The user is created with the model of the application, the same way the
     * "sivarcart-core:create-admin" command does it.
     */
    public function store(Request $request): RedirectResponse
    {
        $model = Auth::newModel();

        $attributes = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique($model->getTable(), 'email'),
            ],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'roles' => ['array'],
            'roles.*' => ['string', 'exists:sivarcart_store_roles,slug'],
        ]);

        if (! method_exists($model, 'assignStoreRole')) {
            abort(403, __('sivarcart-core::admin.staff.without_roles'));
        }

        $user = $model->newInstance();

        $user->forceFill([
            'name' => $attributes['name'],
            'email' => $attributes['email'],
            'password' => $attributes['password'],
        ])->save();

        app(StaffService::class)->assignRoles($user, $attributes['roles'] ?? []);

        Inertia::flash('notice', __('sivarcart-core::admin.staff.created', [
            'name' => (string) $attributes['name'],
        ]));

        return back()->setStatusCode(303);
    }
}
