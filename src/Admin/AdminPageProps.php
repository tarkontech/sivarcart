<?php

declare(strict_types=1);

namespace Tarkontech\SivarCart\Admin;

use Illuminate\Http\Request;

/**
 * The data of a page of the panel.
 *
 * A page of the panel is a component of the bundle, and the data it shows
 * comes from the server. This contract is what brings that data: the panel
 * asks the resolver of the page and hands the answer to the component.
 *
 * A page without a resolver shows the props the panel shares with every page
 * and nothing else.
 */
interface AdminPageProps
{
    /**
     * The data the page needs.
     *
     * @return array<string, mixed>
     */
    public function props(Request $request, AdminPage $page): array;
}
