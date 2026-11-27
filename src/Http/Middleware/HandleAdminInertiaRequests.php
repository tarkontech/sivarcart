<?php

declare(strict_types=1);

namespace Tarkontech\SivarCart\Http\Middleware;

use Closure;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Log;
use Inertia\Middleware;
use Inertia\Response;
use ReflectionProperty;
use RuntimeException;
use Tarkontech\SivarCart\Admin\AdminBundle;
use Tarkontech\SivarCart\Admin\AdminPanel;
use Tarkontech\SivarCart\Admin\AdminPermissions;
use Tarkontech\SivarCart\Services\StoreService;
use Tarkontech\SivarCart\Support\Auth;

/**
 * The props the panel shares with every page.
 *
 * The panel is an Inertia application of its own: it has its own root view
 * and its own middleware, and they are registered only in the routes of the
 * panel. An application that already has an Inertia of its own keeps it
 * untouched.
 *
 * The middleware of the panel runs after the middleware of the application,
 * so the props of the panel are the ones that reach the page of the panel.
 *
 * The names the panel owns are reserved. A page that uses one of them breaks
 * the navigation of the panel or the data of the staff, so the middleware says
 * so instead of letting it pass in silence.
 */
final class HandleAdminInertiaRequests extends Middleware
{
    /**
     * The names the panel owns.
     *
     * @var list<string>
     */
    public const array RESERVED = [
        'nav',
        'store',
        'user',
        'permissions',
        'current',
        'locale',
        'text',
    ];

    /**
     * The root view of the panel.
     *
     * It lives in a namespace of its own, so the views the merchant publishes
     * in "resources/views/vendor/sivarcart" never reach it by accident.
     *
     * @var string
     */
    protected $rootView = 'sivarcart-panel::app';

    /**
     * The version of the assets of the panel.
     */
    public function version(Request $request): string
    {
        return AdminBundle::version();
    }

    /**
     * The props every page of the panel receives.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'nav' => $this->nav($request),
            'store' => $this->store(),
            'user' => $this->user(),
            'permissions' => $this->permissions(),
            'current' => $this->current($request),
            'locale' => app()->getLocale(),
            'text' => $this->text(),
        ];
    }

    /**
     * The texts of the panel, in the language of the application.
     *
     * The panel speaks the language of the application, so the texts travel
     * from the server and the components of the panel do not carry a language
     * of their own.
     *
     * @return array<string, mixed>
     */
    private function text(): array
    {
        $text = __('sivarcart::admin');

        return is_array($text) ? $text : [];
    }

    /**
     * Handle the request and watch the names the panel owns.
     */
    public function handle(Request $request, Closure $next): mixed
    {
        $response = parent::handle($request, $next);

        $this->guardReservedNames($response);

        return $response;
    }

    /**
     * Say so when a page uses one of the names the panel owns.
     *
     * The props of the page win over the props the panel shares, so a page
     * that uses one of those names takes it away from the panel. The name is
     * only reported: the panel keeps working, because a broken panel is worse
     * than a warning.
     */
    private function guardReservedNames(mixed $response): void
    {
        if (! $response instanceof Response) {
            return;
        }

        $props = $this->propertyOf($response, 'props');

        if (! is_array($props)) {
            return;
        }

        $used = array_values(array_intersect(self::RESERVED, array_keys($props)));

        if ($used === []) {
            return;
        }

        $message = sprintf(
            'SivarCart: the page [%s] uses the names [%s] that the panel of the administration owns. The panel shares them with every page, so the page takes them away from it. Rename them.',
            $this->propertyOf($response, 'component') ?? 'unknown',
            implode(', ', $used),
        );

        if (app()->hasDebugModeEnabled()) {
            throw new RuntimeException($message);
        }

        Log::warning($message);
    }

    /**
     * A property of the response of Inertia.
     */
    private function propertyOf(Response $response, string $name): mixed
    {
        $property = new ReflectionProperty($response, $name);

        return $property->getValue($response);
    }

    /**
     * The navigation of the panel.
     *
     * The label of a page of the package is the key of its translation, so the
     * panel speaks the language of the application. The label of a page the
     * application registers is the text it wants.
     *
     * @return list<array{key: string, label: string, icon: string|null, url: string, group: string|null, active: bool}>
     */
    private function nav(Request $request): array
    {
        $current = $this->current($request);

        $nav = [];

        foreach (app(AdminPanel::class)->visiblePages() as $page) {
            $nav[] = [
                'key' => $page->key,
                'label' => (string) __($page->label),
                'icon' => $page->icon,
                'url' => $page->url(),
                'group' => $page->group,
                'active' => $page->key === $current,
            ];
        }

        return $nav;
    }

    /**
     * The store the panel belongs to.
     *
     * @return array{name: string|null, logo: string|null}
     */
    private function store(): array
    {
        $store = app(StoreService::class)->current();

        if (! $store instanceof Model) {
            return ['name' => null, 'logo' => null];
        }

        $name = $store->getAttribute('name');
        $logo = $store->getAttribute('logo_path');

        return [
            'name' => is_string($name) ? $name : null,
            'logo' => is_string($logo) ? $logo : null,
        ];
    }

    /**
     * The member of the staff that is signed in.
     *
     * @return array{name: string|null, email: string|null, roles: list<string>}
     */
    private function user(): array
    {
        $user = Auth::user();

        if (! $user instanceof Authenticatable) {
            return ['name' => null, 'email' => null, 'roles' => []];
        }

        return [
            'name' => $this->attributeOf($user, 'name'),
            'email' => $this->attributeOf($user, 'email'),
            'roles' => $this->roles($user),
        ];
    }

    /**
     * An attribute of the user of the application.
     */
    private function attributeOf(Authenticatable $user, string $attribute): ?string
    {
        if (! $user instanceof Model) {
            return null;
        }

        $value = $user->getAttribute($attribute);

        return is_string($value) ? $value : null;
    }

    /**
     * The roles of the staff that is signed in.
     *
     * @return list<string>
     */
    private function roles(Authenticatable $user): array
    {
        if (! method_exists($user, 'storeRoles')) {
            return [];
        }

        $slugs = [];

        foreach ($user->storeRoles()->get() as $role) {
            $slugs[] = $role->slug;
        }

        return $slugs;
    }

    /**
     * The permissions the staff that is signed in holds.
     *
     * The list is expanded against the vocabulary of the store, so the panel
     * knows which buttons it can show even when the role grants them with a
     * wildcard.
     *
     * @return list<string>
     */
    private function permissions(): array
    {
        $user = Auth::user();

        if (! $user instanceof Authenticatable) {
            return [];
        }

        $held = [];

        foreach (AdminPermissions::all() as $permission) {
            if (Auth::hasStorePermission($user, $permission)) {
                $held[] = $permission;
            }
        }

        return $held;
    }

    /**
     * The page the staff is looking at.
     */
    private function current(Request $request): ?string
    {
        $route = $request->route();

        if (! $route instanceof Route) {
            return null;
        }

        $parameter = $route->parameter('page');

        if (is_string($parameter) && $parameter !== '') {
            return $parameter;
        }

        return match ($route->getName()) {
            'sivarcart.admin.index' => 'dashboard',
            'sivarcart.admin.orders.show' => 'orders',
            default => null,
        };
    }
}
