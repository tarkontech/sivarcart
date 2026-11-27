<?php

declare(strict_types=1);

namespace Tarkontech\SivarCart\Admin;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;
use Tarkontech\SivarCart\Models\Brand;
use Tarkontech\SivarCart\Models\Category;
use Tarkontech\SivarCart\Models\Customer;
use Tarkontech\SivarCart\Models\Order;
use Tarkontech\SivarCart\Models\PaymentMethod;
use Tarkontech\SivarCart\Models\Product;
use Tarkontech\SivarCart\Models\ProductVariant;
use Tarkontech\SivarCart\Models\ShippingMethod;
use Tarkontech\SivarCart\Models\StoreRole;
use Tarkontech\SivarCart\Support\Media;
use Throwable;

/**
 * The shape the panel shows.
 *
 * The panel is an Inertia application and the props it receives are plain
 * data: a model is never handed to the browser as it is. This class is what
 * decides what leaves the server, so a field the panel does not show —the
 * notes of the order, the metadata of the store— never travels.
 */
final class AdminPresenter
{
    /**
     * The tone a state of the store is shown with.
     *
     * The tone says at a glance how the staff has to read the state. The
     * panel maps it to the colors of its own design system.
     */
    public static function tone(string $value): string
    {
        return match ($value) {
            'paid', 'delivered', 'published', 'active', 'completed' => 'success',
            'pending', 'processing', 'unpaid', 'reserved' => 'warning',
            'failed', 'cancelled', 'refunded', 'returned', 'draft', 'inactive' => 'danger',
            default => 'neutral',
        };
    }

    /**
     * One order of the list of the panel.
     *
     * @return array{uuid: string, number: string, customer: string, date: string|null, total: float, currency: string, status: array{value: string, label: string, tone: string}, payment: array{value: string, label: string, tone: string}}
     */
    public static function orderListItem(Order $order): array
    {
        return [
            'uuid' => (string) $order->uuid,
            'number' => (string) $order->order_number,
            'customer' => (string) $order->customer_name,
            'date' => self::date($order->placed_at),
            'total' => (float) $order->total,
            'currency' => (string) $order->currency,
            'status' => self::state($order->status->value, $order->status->label()),
            'payment' => self::state($order->payment_status->value, $order->payment_status->label()),
        ];
    }

    /**
     * One order of the detail of the panel.
     *
     * @return array{uuid: string, number: string, date: string|null, currency: string, status: array{value: string, label: string, tone: string}, payment: array{value: string, label: string, tone: string}, items: list<array{name: string, quantity: int, unit_price: float, line_total: float}>, totals: array{subtotal: float, discount: float, tax: float, shipping: float, total: float}, buyer: array{name: string, email: string, phone: string}, timeline: list<array{date: string|null, text: string|null}>}
     */
    public static function orderDetail(Order $order): array
    {
        $items = [];

        foreach ($order->items as $item) {
            $items[] = [
                'name' => (string) $item->name,
                'quantity' => (int) $item->quantity,
                'unit_price' => (float) $item->unit_price,
                'line_total' => (float) $item->line_total,
            ];
        }

        $timeline = [];

        foreach ($order->statusHistories->sortByDesc('created_at') as $history) {
            $state = $history->axis->value === 'payment'
                ? $history->payment_status?->label()
                : $history->status?->label();

            $timeline[] = [
                'date' => self::date($history->created_at),
                'text' => $state,
            ];
        }

        return [
            'uuid' => (string) $order->uuid,
            'number' => (string) $order->order_number,
            'date' => self::date($order->placed_at),
            'currency' => (string) $order->currency,
            'status' => self::state($order->status->value, $order->status->label()),
            'payment' => self::state($order->payment_status->value, $order->payment_status->label()),
            'items' => $items,
            'totals' => [
                'subtotal' => (float) $order->subtotal,
                'discount' => (float) $order->discount_total,
                'tax' => (float) $order->tax_total,
                'shipping' => (float) $order->shipping_total,
                'total' => (float) $order->total,
            ],
            'buyer' => [
                'name' => (string) $order->customer_name,
                'email' => (string) $order->customer_email,
                'phone' => (string) $order->customer_phone,
            ],
            'timeline' => $timeline,
        ];
    }

    /**
     * One product of the catalog of the panel.
     *
     * @return array{uuid: string, name: string, sku: string|null, price_min: float|null, price_max: float|null, stock: int, status: array{value: string, label: string, tone: string}}
     */
    public static function productListItem(Product $product): array
    {
        $range = $product->priceRange();
        $variant = $product->defaultVariant();

        return [
            'uuid' => (string) $product->uuid,
            'name' => (string) $product->name,
            'sku' => $product->sku(),
            'price_min' => $range === null ? null : (float) $range['min'],
            'price_max' => $range === null ? null : (float) $range['max'],
            'stock' => $variant === null ? 0 : (int) $variant->stock_quantity,
            'status' => self::state($product->status->value, $product->status->label()),
        ];
    }

    /**
     * The product the form of the panel writes.
     *
     * A product that does not exist yet is an empty form, so the screen of
     * "new product" and the screen of "edit product" are the same one.
     *
     * @return array{uuid: string|null, name: string, slug: string, short_description: string, description: string, status: string, type: string, is_featured: bool, is_virtual: bool, brand_id: int|null, weight: float|null, categories: list<int>, variants: list<array{uuid: string|null, name: string, sku: string, barcode: string, price: string, compare_at_price: string, cost_price: string, stock_quantity: int, low_stock_threshold: int|null, is_default: bool, is_active: bool}>}
     */
    public static function productForm(?Product $product): array
    {
        if (! $product instanceof Product) {
            return [
                'uuid' => null,
                'name' => '',
                'slug' => '',
                'short_description' => '',
                'description' => '',
                'status' => 'draft',
                'type' => 'simple',
                'is_featured' => false,
                'is_virtual' => false,
                'brand_id' => null,
                'weight' => null,
                'categories' => [],
                'variants' => [self::emptyVariant()],
            ];
        }

        $categories = [];

        foreach ($product->categories as $category) {
            $categories[] = (int) $category->getKey();
        }

        $variants = [];

        foreach ($product->variants->sortBy('position') as $variant) {
            $variants[] = self::variant($variant);
        }

        return [
            'uuid' => (string) $product->uuid,
            'name' => (string) $product->name,
            'slug' => (string) $product->slug,
            'short_description' => (string) ($product->short_description ?? ''),
            'description' => (string) ($product->description ?? ''),
            'status' => $product->status->value,
            'type' => $product->type->value,
            'is_featured' => (bool) $product->is_featured,
            'is_virtual' => (bool) $product->is_virtual,
            'brand_id' => $product->brand_id === null ? null : (int) $product->brand_id,
            'weight' => $product->weight === null ? null : (float) $product->weight,
            'categories' => $categories,
            'variants' => $variants,
        ];
    }

    /**
     * One variant of the form of the panel.
     *
     * @return array{uuid: string|null, name: string, sku: string, barcode: string, price: string, compare_at_price: string, cost_price: string, stock_quantity: int, low_stock_threshold: int|null, is_default: bool, is_active: bool}
     */
    public static function variant(ProductVariant $variant): array
    {
        return [
            'uuid' => (string) $variant->uuid,
            'name' => (string) ($variant->name ?? ''),
            'sku' => (string) ($variant->sku ?? ''),
            'barcode' => (string) ($variant->barcode ?? ''),
            'price' => (string) $variant->price,
            'compare_at_price' => (string) ($variant->compare_at_price ?? ''),
            'cost_price' => (string) ($variant->cost_price ?? ''),
            'stock_quantity' => (int) $variant->stock_quantity,
            'low_stock_threshold' => $variant->low_stock_threshold === null ? null : (int) $variant->low_stock_threshold,
            'is_default' => (bool) $variant->is_default,
            'is_active' => (bool) $variant->is_active,
        ];
    }

    /**
     * The variant of a product that does not exist yet.
     *
     * A product always owns a sellable unit, so the form of a new product
     * starts with one.
     *
     * @return array{uuid: string|null, name: string, sku: string, barcode: string, price: string, compare_at_price: string, cost_price: string, stock_quantity: int, low_stock_threshold: int|null, is_default: bool, is_active: bool}
     */
    public static function emptyVariant(): array
    {
        return [
            'uuid' => null,
            'name' => '',
            'sku' => '',
            'barcode' => '',
            'price' => '0',
            'compare_at_price' => '',
            'cost_price' => '',
            'stock_quantity' => 0,
            'low_stock_threshold' => null,
            'is_default' => true,
            'is_active' => true,
        ];
    }

    /**
     * One category of the catalog of the panel.
     *
     * @return array{uuid: string, id: int, name: string, slug: string, description: string, parent_id: int|null, position: int, is_active: bool, is_featured: bool, products: int}
     */
    public static function category(Category $category): array
    {
        return [
            'uuid' => (string) $category->uuid,
            'id' => (int) $category->getKey(),
            'name' => (string) $category->name,
            'slug' => (string) $category->slug,
            'description' => (string) ($category->description ?? ''),
            'parent_id' => $category->parent_id === null ? null : (int) $category->parent_id,
            'position' => (int) $category->position,
            'is_active' => (bool) $category->is_active,
            'is_featured' => (bool) $category->is_featured,
            'products' => (int) ($category->products_count ?? 0),
        ];
    }

    /**
     * One brand of the catalog of the panel.
     *
     * @return array{id: int, name: string, slug: string, description: string, position: int, is_active: bool, products: int, logo: string|null}
     */
    public static function brand(Brand $brand): array
    {
        return [
            'id' => (int) $brand->getKey(),
            'name' => (string) $brand->name,
            'slug' => (string) $brand->slug,
            'description' => (string) ($brand->description ?? ''),
            'position' => (int) $brand->position,
            'is_active' => (bool) $brand->is_active,
            'products' => (int) ($brand->products_count ?? 0),
            'logo' => self::fileUrl($brand->logo_path),
        ];
    }

    /**
     * One shipping method of the panel.
     *
     * @return array{uuid: string, code: string, name: string, type: string, type_label: string, price: float, min_order_amount: float|null, max_order_amount: float|null, estimated_days: int|null, instructions: string, position: int, is_active: bool}
     */
    public static function shippingMethod(ShippingMethod $method): array
    {
        return [
            'uuid' => (string) $method->uuid,
            'code' => (string) $method->code,
            'name' => (string) $method->name,
            'type' => $method->type->value,
            'type_label' => $method->type->label(),
            'price' => (float) $method->price,
            'min_order_amount' => $method->min_order_amount === null ? null : (float) $method->min_order_amount,
            'max_order_amount' => $method->max_order_amount === null ? null : (float) $method->max_order_amount,
            'estimated_days' => $method->estimated_days === null ? null : (int) $method->estimated_days,
            'instructions' => (string) ($method->instructions ?? ''),
            'position' => (int) $method->position,
            'is_active' => (bool) $method->is_active,
        ];
    }

    /**
     * One payment method of the panel.
     *
     * The credentials never travel: the panel is told whether they are there
     * and not what they are.
     *
     * @return array{uuid: string, code: string, name: string, type: string, type_label: string, driver: string|null, instructions: string, icon: string|null, position: int, is_active: bool, has_credentials: bool}
     */
    public static function paymentMethod(PaymentMethod $method): array
    {
        $credentials = $method->credentials;

        return [
            'uuid' => (string) $method->uuid,
            'code' => (string) $method->code,
            'name' => (string) $method->name,
            'type' => $method->type->value,
            'type_label' => $method->type->label(),
            'driver' => $method->driver === null ? null : (string) $method->driver,
            'instructions' => (string) ($method->instructions ?? ''),
            'icon' => $method->icon === null ? null : (string) $method->icon,
            'position' => (int) $method->position,
            'is_active' => (bool) $method->is_active,
            'has_credentials' => is_array($credentials) && $credentials !== [],
        ];
    }

    /**
     * The credentials of a payment method, as the form carries them.
     *
     * The values are not sent: the staff only sees the keys, and writes the
     * values again when they want to change them.
     *
     * @return list<array{key: string, value: string}>
     */
    public static function paymentCredentials(PaymentMethod $method): array
    {
        $credentials = $method->credentials;

        if (! is_array($credentials)) {
            return [];
        }

        $pairs = [];

        foreach ($credentials as $key => $value) {
            if (! is_string($key)) {
                continue;
            }

            $pairs[] = ['key' => $key, 'value' => ''];
        }

        return $pairs;
    }

    /**
     * One member of the staff of the store.
     *
     * @param  list<string>  $roles
     * @return array{id: int, name: string|null, email: string|null, roles: list<string>}
     */
    public static function staffMember(Model $user, array $roles): array
    {
        $name = $user->getAttribute('name');
        $email = $user->getAttribute('email');

        return [
            'id' => (int) $user->getKey(),
            'name' => is_string($name) ? $name : null,
            'email' => is_string($email) ? $email : null,
            'roles' => $roles,
        ];
    }

    /**
     * The address of a file of the store.
     */
    public static function fileUrl(?string $path): ?string
    {
        if ($path === null || $path === '') {
            return null;
        }

        try {
            return Storage::disk(Media::disk())->url($path);
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * One buyer of the panel.
     *
     * @return array{email: string, name: string, phone: string, orders: int}
     */
    public static function customerListItem(Customer $customer): array
    {
        $name = trim($customer->first_name.' '.$customer->last_name);

        return [
            'email' => (string) $customer->email,
            'name' => $name,
            'phone' => (string) ($customer->phone ?? ''),
            'orders' => (int) ($customer->orders_count ?? 0),
        ];
    }

    /**
     * One role of the staff of the store.
     *
     * The permissions of the vocabulary that the role grants are sent apart
     * from the ones it holds: a role that holds "*" grants everything, and
     * the panel has to show that without pretending the role lists them one
     * by one.
     *
     * @return array{name: string, slug: string, description: string, permissions: list<string>, granted: list<string>, staff: int}
     */
    public static function role(StoreRole $role): array
    {
        $permissions = [];

        foreach ($role->permissions ?? [] as $permission) {
            if (is_string($permission)) {
                $permissions[] = $permission;
            }
        }

        $granted = [];

        foreach (AdminPermissions::all() as $permission) {
            if ($role->hasPermission($permission)) {
                $granted[] = $permission;
            }
        }

        return [
            'name' => (string) $role->name,
            'slug' => (string) $role->slug,
            'description' => (string) ($role->description ?? ''),
            'permissions' => $permissions,
            'granted' => $granted,
            'staff' => (int) ($role->users_count ?? 0),
        ];
    }

    /**
     * A page of a list, with the data the panel shows.
     *
     * The items are presented one by one, so the panel never receives a model
     * as it is. The shape is the one the panel needs: the rows and the numbers
     * of the pagination.
     *
     * @template T of \Illuminate\Database\Eloquent\Model
     *
     * @param  LengthAwarePaginator<int, T>  $paginator
     * @param  callable(T): array<string, mixed>  $present
     * @return array{data: list<array<string, mixed>>, meta: array{current_page: int, last_page: int, per_page: int, total: int, from: int|null, to: int|null}}
     */
    public static function paginate(LengthAwarePaginator $paginator, callable $present): array
    {
        $items = [];

        foreach ($paginator->items() as $item) {
            $items[] = $present($item);
        }

        return [
            'data' => $items,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
        ];
    }

    /**
     * A state of the store, with its label and the tone it is shown with.
     *
     * @return array{value: string, label: string, tone: string}
     */
    public static function state(string $value, string $label): array
    {
        return [
            'value' => $value,
            'label' => $label,
            'tone' => self::tone($value),
        ];
    }

    /**
     * A date of the panel, in the format the panel has always used.
     */
    public static function date(?DateTimeInterface $date): ?string
    {
        return $date?->format('d/m/Y H:i');
    }
}
