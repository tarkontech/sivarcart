<?php

declare(strict_types=1);

namespace Tarkontech\SivarCart\Admin;

use Tarkontech\SivarCart\Support\Auth;

/**
 * The registry of the administration panel.
 *
 * The panel is made of pages and widgets. The package registers its own, and
 * the host application registers the ones it needs:
 *
 *     SivarCart::admin()->page(
 *         key: 'quick-view',
 *         label: 'Vista rápida',
 *         permission: 'orders.view',
 *         component: 'Admin/QuickView',
 *     );
 *
 * The component of a page is the name of the component, not a class: it is the
 * name that the bundle of the panel resolves. A merchant that builds its own
 * bundle puts its own component under that name and the panel shows it.
 *
 * The registry is a singleton of the container, so the pages the application
 * registers in the boot of its provider are known by the panel, and the ones it
 * registers later are known too.
 */
class AdminPanel
{
    /**
     * The pages of the panel, by key.
     *
     * @var array<string, AdminPage>
     */
    private array $pages = [];

    /**
     * The widgets of the dashboard, by key.
     *
     * @var array<string, AdminWidget>
     */
    private array $widgets = [];

    /**
     * Register a page of the panel.
     *
     * The component of a page is the name of the component of the bundle of
     * the panel ("Admin/Orders/Index"). A page without a component belongs to
     * the application and is answered by its own route.
     */
    public function page(
        string $key,
        string $label,
        ?string $icon = null,
        ?string $permission = null,
        ?string $component = null,
        ?AdminPageProps $props = null,
        ?string $route = null,
        ?string $group = null,
        int $order = 100,
    ): AdminPage {
        $page = new AdminPage(
            key: $key,
            label: $label,
            icon: $icon,
            permission: $permission,
            component: $component,
            props: $props,
            route: $route,
            group: $group,
            order: $order,
        );

        $this->pages[$key] = $page;

        return $page;
    }

    /**
     * Register a widget of the dashboard.
     *
     * The component of a widget is the name of the component of the bundle of
     * the panel ("Admin/Widgets/LowStock"), and its data comes from the
     * resolver the widget carries.
     */
    public function widget(
        string $key,
        string $component,
        ?AdminWidgetProps $props = null,
        ?string $permission = null,
        int $order = 100,
    ): AdminWidget {
        $widget = new AdminWidget(
            key: $key,
            component: $component,
            props: $props,
            permission: $permission,
            order: $order,
        );

        $this->widgets[$key] = $widget;

        return $widget;
    }

    /**
     * Every page of the panel, in the order of the navigation.
     *
     * @return list<AdminPage>
     */
    public function pages(): array
    {
        $pages = array_values($this->pages);

        usort(
            $pages,
            fn (AdminPage $left, AdminPage $right): int => [$left->group ?? '', $left->order, $left->key]
                <=> [$right->group ?? '', $right->order, $right->key],
        );

        return $pages;
    }

    /**
     * The pages the staff of the store is allowed to see.
     *
     * @return list<AdminPage>
     */
    public function visiblePages(): array
    {
        return array_values(array_filter(
            $this->pages(),
            fn (AdminPage $page): bool => $this->allows($page->permission),
        ));
    }

    /**
     * The widgets the staff of the store is allowed to see.
     *
     * @return list<AdminWidget>
     */
    public function visibleWidgets(): array
    {
        $widgets = array_values(array_filter(
            $this->widgets,
            fn (AdminWidget $widget): bool => $this->allows($widget->permission),
        ));

        usort(
            $widgets,
            fn (AdminWidget $left, AdminWidget $right): int => [$left->order, $left->key]
                <=> [$right->order, $right->key],
        );

        return $widgets;
    }

    /**
     * Every widget of the dashboard.
     *
     * @return list<AdminWidget>
     */
    public function widgets(): array
    {
        return array_values($this->widgets);
    }

    /**
     * A page of the panel, by key.
     */
    public function find(string $key): ?AdminPage
    {
        return $this->pages[$key] ?? null;
    }

    /**
     * Whether the staff of the store holds a permission.
     *
     * A page or a widget without a permission is open to every member of the
     * staff that is signed in.
     */
    public function allows(?string $permission): bool
    {
        if ($permission === null) {
            return true;
        }

        return Auth::hasStorePermission(Auth::user(), $permission);
    }
}
