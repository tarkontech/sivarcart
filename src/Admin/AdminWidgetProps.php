<?php

declare(strict_types=1);

namespace Tarkontech\SivarCart\Admin;

use Illuminate\Http\Request;

/**
 * The data of a widget of the dashboard.
 *
 * A widget of the dashboard is a component of the bundle of the panel, and
 * the data it shows comes from the server. This contract is what brings that
 * data: the dashboard asks the resolver of the widget and hands the answer to
 * the component.
 *
 * A widget without a resolver shows nothing but its own frame.
 */
interface AdminWidgetProps
{
    /**
     * The data the widget needs.
     *
     * @return array<string, mixed>
     */
    public function props(Request $request): array;
}
