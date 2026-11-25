<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Admin;

/**
 * A page of the administration panel.
 *
 * A page is what the navigation of the panel shows. The package registers its
 * own pages and the host application can register its own, so a merchant that
 * needs a screen the package does not have does not have to touch the package.
 *
 * A page is rendered in one of two ways:
 *
 * - With a component, the package renders a Livewire component and the page
 *   lives under the prefix of the panel: "/sivarcart/admin/quick-view".
 * - With a route, the page is a route of the application and the package only
 *   puts the link in the navigation.
 */
final readonly class AdminPage
{
    /**
     * @param  string  $key  The key of the page, also the segment of its URL.
     * @param  string  $label  The name of the page in the navigation.
     * @param  string|null  $icon  The name of the icon of the page.
     * @param  string|null  $permission  The permission the staff needs to see it.
     * @param  string|null  $component  The name of the Livewire component of the page.
     * @param  string|null  $componentClass  The class of the Livewire component.
     * @param  string|null  $route  The route of the application that answers it.
     * @param  string|null  $group  The group of the navigation it belongs to.
     * @param  int  $order  The position of the page in the navigation.
     */
    public function __construct(
        public string $key,
        public string $label,
        public ?string $icon = null,
        public ?string $permission = null,
        public ?string $component = null,
        public ?string $componentClass = null,
        public ?string $route = null,
        public ?string $group = null,
        public int $order = 100,
    ) {
        //
    }

    /**
     * Whether the page is rendered by the package.
     */
    public function belongsToPackage(): bool
    {
        return $this->component !== null;
    }

    /**
     * The URL of the page.
     *
     * A page with a route belongs to the application, so the URL is the one of
     * that route. A page with a component lives under the prefix of the panel.
     */
    public function url(): string
    {
        if ($this->route !== null) {
            return route($this->route);
        }

        return url((string) config('sivarcart-core.admin.prefix', 'sivarcart/admin').'/'.$this->key);
    }
}
