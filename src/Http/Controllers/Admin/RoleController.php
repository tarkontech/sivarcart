<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use SivarcartCore\SivarcartCore\Models\StoreRole;
use SivarcartCore\SivarcartCore\Services\Admin\RoleService;

/**
 * Writes the roles of the staff of the store.
 *
 * A role is a name and the permissions it grants, so writing a role is
 * writing the permissions of a group of people. The permissions the panel
 * offers are the ones the package knows: the vocabulary of the store.
 */
final class RoleController
{
    /**
     * Create a role of the store.
     */
    public function store(Request $request): RedirectResponse
    {
        $attributes = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:255'],
            'permissions' => ['array'],
            'permissions.*' => ['string'],
        ]);

        app(RoleService::class)->create($attributes);

        Inertia::flash('notice', __('sivarcart-core::admin.roles.created', [
            'name' => (string) $attributes['name'],
        ]));

        return back()->setStatusCode(303);
    }

    /**
     * Write a role of the store.
     */
    public function update(Request $request, string $role): RedirectResponse
    {
        $model = app(RoleService::class)->find($role);

        if (! $model instanceof StoreRole) {
            abort(404, __('sivarcart-core::admin.roles.not_found'));
        }

        $attributes = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:255'],
            'permissions' => ['array'],
            'permissions.*' => ['string'],
        ]);

        app(RoleService::class)->update($model, $attributes);

        Inertia::flash('notice', __('sivarcart-core::admin.roles.saved', [
            'name' => (string) $model->name,
        ]));

        return back()->setStatusCode(303);
    }

    /**
     * Remove a role of the store.
     *
     * The roles that the package brought are not removed: they are the ones
     * the panel and the command of the installation expect to find.
     */
    public function destroy(string $role): RedirectResponse
    {
        $model = app(RoleService::class)->find($role);

        if (! $model instanceof StoreRole) {
            abort(404, __('sivarcart-core::admin.roles.not_found'));
        }

        if ($model->is_system) {
            return back()
                ->withErrors(['role' => __('sivarcart-core::admin.roles.system_role')])
                ->setStatusCode(303);
        }

        app(RoleService::class)->delete($model);

        Inertia::flash('notice', __('sivarcart-core::admin.roles.deleted', [
            'name' => (string) $model->name,
        ]));

        return back()->setStatusCode(303);
    }
}
