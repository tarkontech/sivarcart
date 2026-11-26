<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use SivarcartCore\SivarcartCore\Services\StoreService;

/**
 * Writes the settings of the store.
 *
 * The settings are written by the service of the store, the same one the rest
 * of the package reads, so the panel never writes them by itself.
 */
final class SettingsController
{
    /**
     * Save the settings of the store.
     */
    public function update(Request $request): RedirectResponse
    {
        $attributes = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'legal_name' => ['nullable', 'string', 'max:255'],
            'tax_id' => ['nullable', 'string', 'max:64'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:32'],
        ]);

        app(StoreService::class)->update($attributes);

        Inertia::flash('notice', __('sivarcart-core::admin.settings.saved'));

        return back()->setStatusCode(303);
    }
}
