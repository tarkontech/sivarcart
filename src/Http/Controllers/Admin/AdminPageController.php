<?php

declare(strict_types=1);

namespace Tarkontech\SivarCart\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Tarkontech\SivarCart\Admin\AdminPage;
use Tarkontech\SivarCart\Admin\AdminPanel;

/**
 * Renders the pages of the administration panel.
 *
 * The pages are not routes of the package: they are the ones the panel
 * registry knows. The package registers its own pages and the host application
 * registers the ones it needs, so this controller only has to look the page up
 * and hand it to the bundle of the panel.
 */
final class AdminPageController
{
    /**
     * Show the home of the panel.
     */
    public function index(Request $request): Response
    {
        return $this->show($request, 'dashboard');
    }

    /**
     * Show a page of the panel.
     */
    public function show(Request $request, string $page): Response
    {
        $panel = app(AdminPanel::class);

        $registered = $panel->find($page);

        if (! $registered instanceof AdminPage || ! $registered->belongsToPackage()) {
            abort(404, 'La página no existe.');
        }

        if (! $panel->allows($registered->permission)) {
            abort(403, 'No tienes permiso para ver esta página.');
        }

        return Inertia::render(
            (string) $registered->component,
            $registered->props?->props($request, $registered) ?? [],
        );
    }
}
