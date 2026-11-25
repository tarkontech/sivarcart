<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Admin;

/**
 * A widget of the dashboard of the administration panel.
 *
 * A widget is a Livewire component the dashboard renders in a grid. The
 * package registers its own widgets and the host application can register its
 * own, so a merchant can add the numbers it cares about without touching the
 * package.
 */
final readonly class AdminWidget
{
    /**
     * @param  string  $key  The key of the widget.
     * @param  string  $component  The name of the Livewire component of the widget.
     * @param  string|null  $componentClass  The class of the Livewire component.
     * @param  string|null  $permission  The permission the staff needs to see it.
     * @param  int  $order  The position of the widget in the grid.
     */
    public function __construct(
        public string $key,
        public string $component,
        public ?string $componentClass = null,
        public ?string $permission = null,
        public int $order = 100,
    ) {
        //
    }
}
