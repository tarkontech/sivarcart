<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Http\Middleware;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Log;
use Inertia\Middleware;
use Inertia\Response;
use ReflectionProperty;
use RuntimeException;
use SivarcartCore\SivarcartCore\Models\Customer;
use SivarcartCore\SivarcartCore\Services\CartService;
use SivarcartCore\SivarcartCore\Services\CatalogService;
use SivarcartCore\SivarcartCore\Services\CustomerService;
use SivarcartCore\SivarcartCore\Services\StoreService;
use SivarcartCore\SivarcartCore\Storefront\StorefrontBundle;
use SivarcartCore\SivarcartCore\Storefront\StorefrontPresenter;

/**
 * The props the store shares with every page.
 *
 * The store is an Inertia application of its own: it has its own root view
 * and its own middleware, and they are registered only in the routes of the
 * store. An application that already has an Inertia of its own keeps it
 * untouched.
 *
 * The middleware of the store runs after the middleware of the application,
 * so the props of the store are the ones that reach the page of the store.
 *
 * The names the store owns are reserved. A page that uses one of them breaks
 * the header or the data of the shopper, so the middleware says so instead of
 * letting it pass in silence.
 */
final class HandleStorefrontInertiaRequests extends Middleware
{
    /**
     * The names the store owns.
     *
     * @var list<string>
     */
    public const array RESERVED = [
        'store',
        'cart',
        'buyer',
        'categories',
        'brands',
        'current',
        'locale',
        'prefix',
        'text',
    ];

    /**
     * The root view of the store.
     *
     * It lives in a namespace of its own, so the views the merchant publishes
     * in "resources/views/vendor/sivarcart-core" never reach it by accident.
     *
     * @var string
     */
    protected $rootView = 'sivarcart-core-storefront::app';

    /**
     * The version of the assets of the store.
     */
    public function version(Request $request): string
    {
        return StorefrontBundle::version();
    }

    /**
     * The props every page of the store receives.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'store' => $this->store(),
            'cart' => $this->cart(),
            'buyer' => $this->buyer(),
            'categories' => $this->categories(),
            'brands' => $this->brands(),
            'current' => $this->current($request),
            'locale' => app()->getLocale(),
            'prefix' => $this->prefix(),
            'text' => $this->text(),
        ];
    }

    /**
     * Where the store is mounted.
     *
     * The prefix travels con las props porque el bundle de la tienda arma
     * sus direcciones con él: un comerciante que cambia el prefijo del
     * paquete cambia las direcciones de la tienda y nada más.
     */
    private function prefix(): string
    {
        $prefix = (string) config('sivarcart-core.storefront.prefix', 'tienda');

        return '/'.trim($prefix, '/');
    }

    /**
     * The texts of the store, in the language of the application.
     *
     * The store speaks the language of the application, so the texts travel
     * from the server and the components of the store do not carry a language
     * of their own.
     *
     * @return array<string, mixed>
     */
    private function text(): array
    {
        $text = __('sivarcart-core::storefront');

        return is_array($text) ? $text : [];
    }

    /**
     * Handle the request and watch the names the store owns.
     */
    public function handle(Request $request, Closure $next): mixed
    {
        $response = parent::handle($request, $next);

        $this->guardReservedNames($response);

        return $response;
    }

    /**
     * Say so when a page uses one of the names the store owns.
     *
     * The props of the page win over the props the store shares, so a page
     * that uses one of those names takes it away from the store. The name is
     * only reported: the store keeps working, because a broken store is worse
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
            'SivarCart Core: la página [%s] usa los nombres [%s] que la tienda del comprador posee. La tienda los comparte con todas sus páginas, así que la página se los quita. Renómbralos.',
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
     * The store the shopper is buying in.
     *
     * @return array{name: string|null, logo: string|null, favicon: string|null, currency: string, locale: string, timezone: string, prices_include_tax: bool, email: string|null, phone: string|null, whatsapp: string|null, address: string|null}
     */
    private function store(): array
    {
        $stores = app(StoreService::class);

        $store = $stores->current();

        $name = $store instanceof Model ? $store->getAttribute('name') : null;
        $logo = $store instanceof Model ? $store->getAttribute('logo_path') : null;
        $favicon = $store instanceof Model ? $store->getAttribute('favicon_path') : null;
        $email = $store instanceof Model ? $store->getAttribute('email') : null;
        $phone = $store instanceof Model ? $store->getAttribute('phone') : null;
        $whatsapp = $store instanceof Model ? $store->getAttribute('whatsapp') : null;
        $city = $store instanceof Model ? $store->getAttribute('city') : null;
        $municipality = $store instanceof Model ? $store->getAttribute('municipality') : null;

        return [
            'name' => is_string($name) ? $name : null,
            'logo' => is_string($logo) ? StorefrontPresenter::fileUrl($logo) : null,
            'favicon' => is_string($favicon) ? StorefrontPresenter::fileUrl($favicon) : null,
            'currency' => $stores->currency(),
            'locale' => app()->getLocale(),
            'timezone' => $stores->timezone(),
            'prices_include_tax' => $stores->pricesIncludeTax(),
            'email' => is_string($email) ? $email : null,
            'phone' => is_string($phone) ? $phone : null,
            'whatsapp' => is_string($whatsapp) ? $whatsapp : null,
            'address' => $this->address($municipality, $city),
        ];
    }

    /**
     * The address of the store, as the shopper reads it.
     */
    private function address(mixed $municipality, mixed $city): ?string
    {
        $parts = [];

        if (is_string($municipality) && $municipality !== '') {
            $parts[] = $municipality;
        }

        if (is_string($city) && $city !== '' && ! in_array($city, $parts, true)) {
            $parts[] = $city;
        }

        return $parts === [] ? null : implode(', ', $parts);
    }

    /**
     * The cart of the shopper that is signed in.
     *
     * @return array{count: int, subtotal: float, total: float}
     */
    private function cart(): array
    {
        $buyer = app(CustomerService::class)->user();

        if (! $buyer instanceof Customer) {
            return ['count' => 0, 'subtotal' => 0.0, 'total' => 0.0];
        }

        $cart = app(CartService::class)->find((int) $buyer->getKey());

        if ($cart === null) {
            return ['count' => 0, 'subtotal' => 0.0, 'total' => 0.0];
        }

        return [
            'count' => (int) $cart->item_count,
            'subtotal' => (float) $cart->subtotal,
            'total' => (float) $cart->total,
        ];
    }

    /**
     * The buyer that is signed in.
     *
     * @return array{name: string|null, email: string|null}
     */
    private function buyer(): array
    {
        $buyer = app(CustomerService::class)->user();

        if (! $buyer instanceof Customer) {
            return ['name' => null, 'email' => null];
        }

        return [
            'name' => trim($buyer->first_name.' '.$buyer->last_name),
            'email' => (string) $buyer->email,
        ];
    }

    /**
     * The categories the shopper browses.
     *
     * @return list<array<string, mixed>>
     */
    private function categories(): array
    {
        $categories = [];

        foreach (app(CatalogService::class)->categories() as $category) {
            $categories[] = StorefrontPresenter::category($category);
        }

        return $categories;
    }

    /**
     * The brands of the catalog.
     *
     * @return list<array<string, mixed>>
     */
    private function brands(): array
    {
        $brands = [];

        foreach (app(CatalogService::class)->brands() as $brand) {
            $brands[] = StorefrontPresenter::brand($brand);
        }

        return $brands;
    }

    /**
     * The page the shopper is looking at.
     */
    private function current(Request $request): ?string
    {
        $route = $request->route();

        if (! $route instanceof Route) {
            return null;
        }

        $name = $route->getName();

        if (! is_string($name) || ! str_starts_with($name, 'sivarcart-core.storefront.')) {
            return null;
        }

        return substr($name, strlen('sivarcart-core.storefront.'));
    }
}
