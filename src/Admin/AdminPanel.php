<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Admin;

use Livewire\Livewire;
use SivarcartCore\SivarcartCore\Support\Auth;

/**
 * The registry of the administration panel.
 *
 * The panel is made of pages and widgets. The package registers its own, and
 * the host application registers the ones it needs:
 *
 *     SivarcartCore::admin()->page(
 *         key: 'quick-view',
 *         label: 'Vista rápida',
 *         permission: 'orders.view',
 *         component: QuickView::class,
 *     );
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
     * The component of a page is either the class of a Livewire component or
     * the name of one that is already registered. A class is given the name
     * of the page, and Livewire is told about it when the panel renders.
     */
    public function page(
        string $key,
        string $label,
        ?string $icon = null,
        ?string $permission = null,
        ?string $component = null,
        ?string $route = null,
        ?string $group = null,
        int $order = 100,
    ): AdminPage {
        $class = null;

        if ($component !== null && class_exists($component)) {
            $class = $component;
            $component = 'sivarcart.admin.page.'.$key;
        }

        $page = new AdminPage(
            key: $key,
            label: $label,
            icon: $icon,
            permission: $permission,
            component: $component,
            componentClass: $class,
            route: $route,
            group: $group,
            order: $order,
        );

        $this->pages[$key] = $page;

        return $page;
    }

    /**
     * Register a widget of the dashboard.
     */
    public function widget(
        string $key,
        string $component,
        ?string $permission = null,
        int $order = 100,
    ): AdminWidget {
        $class = null;

        if (class_exists($component)) {
            $class = $component;
            $component = 'sivarcart.admin.widget.'.$key;
        }

        $widget = new AdminWidget(
            key: $key,
            component: $component,
            componentClass: $class,
            permission: $permission,
            order: $order,
        );

        $this->widgets[$key] = $widget;

        return $widget;
    }

    /**
     * Tell Livewire about the components of the pages and of the widgets.
     *
     * Livewire is told about them when the panel renders and not when they are
     * registered, because the application may register a page in the boot of
     * its provider, which happens before the provider of Livewire registers
     * itself.
     */
    public function registerLivewireComponents(): void
    {
        foreach ($this->pages as $page) {
            if ($page->componentClass !== null && $page->component !== null) {
                Livewire::component($page->component, $page->componentClass);
            }
        }

        foreach ($this->widgets as $widget) {
            if ($widget->componentClass !== null) {
                Livewire::component($widget->component, $widget->componentClass);
            }
        }
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
