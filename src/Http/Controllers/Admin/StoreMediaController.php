<?php

declare(strict_types=1);

namespace Tarkontech\SivarCart\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Tarkontech\SivarCart\Models\Store;
use Tarkontech\SivarCart\Services\MediaService;
use Tarkontech\SivarCart\Services\StoreService;
use Throwable;

/**
 * Writes the images of the store.
 *
 * The logo and the favicon are the two files that belong to the store and
 * not to a product, so the panel writes them here. The service of the media
 * owns the files, so a file the store does not accept leaves nothing behind.
 */
final class StoreMediaController
{
    /**
     * The logo of the store.
     */
    public function logo(Request $request): RedirectResponse
    {
        $request->validate([
            'logo' => ['required', 'file'],
        ]);

        $store = app(StoreService::class)->current();

        if (! $store instanceof Store) {
            abort(404, __('sivarcart::admin.settings.not_found'));
        }

        $file = $request->file('logo');

        if ($file === null) {
            return back()
                ->withErrors(['logo' => __('sivarcart::admin.settings.invalid_image')])
                ->setStatusCode(303);
        }

        try {
            app(MediaService::class)->storeStoreLogo($store, $file);
        } catch (Throwable $exception) {
            return back()
                ->withErrors(['logo' => $exception->getMessage()])
                ->setStatusCode(303);
        }

        Inertia::flash('notice', __('sivarcart::admin.settings.logo_saved'));

        return back()->setStatusCode(303);
    }

    /**
     * The favicon of the store.
     */
    public function favicon(Request $request): RedirectResponse
    {
        $request->validate([
            'favicon' => ['required', 'file'],
        ]);

        $store = app(StoreService::class)->current();

        if (! $store instanceof Store) {
            abort(404, __('sivarcart::admin.settings.not_found'));
        }

        $file = $request->file('favicon');

        if ($file === null) {
            return back()
                ->withErrors(['favicon' => __('sivarcart::admin.settings.invalid_image')])
                ->setStatusCode(303);
        }

        try {
            app(MediaService::class)->storeStoreFavicon($store, $file);
        } catch (Throwable $exception) {
            return back()
                ->withErrors(['favicon' => $exception->getMessage()])
                ->setStatusCode(303);
        }

        Inertia::flash('notice', __('sivarcart::admin.settings.favicon_saved'));

        return back()->setStatusCode(303);
    }
}
