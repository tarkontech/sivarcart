<?php

declare(strict_types=1);

namespace Tarkontech\SivarCart\Admin;

/**
 * A widget of the dashboard of the administration panel.
 *
 * A widget is a piece of the dashboard that the grid renders. The package
 * registers its own widgets and the host application can register its own, so
 * a merchant can add the numbers it cares about without touching the package.
 *
 * The widget is the **name** of an Inertia component, the same name that the
 * bundle of the panel resolves. Its data comes from the resolver the widget
 * carries, so the dashboard does not have to know anything about it.
 */
final readonly class AdminWidget
{
    /**
     * @param  string  $key  The key of the widget.
     * @param  string  $component  The name of the Inertia component of the widget.
     * @param  AdminWidgetProps|null  $props  The resolver of the data of the widget.
     * @param  string|null  $permission  The permission the staff needs to see it.
     * @param  int  $order  The position of the widget in the grid.
     */
    public function __construct(
        public string $key,
        public string $component,
        public ?AdminWidgetProps $props = null,
        public ?string $permission = null,
        public int $order = 100,
    ) {
        //
    }
}
