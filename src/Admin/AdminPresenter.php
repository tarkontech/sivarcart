<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Admin;

use DateTimeInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use SivarcartCore\SivarcartCore\Models\Customer;
use SivarcartCore\SivarcartCore\Models\Order;
use SivarcartCore\SivarcartCore\Models\Product;
use SivarcartCore\SivarcartCore\Models\StoreRole;

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
     * @return array{name: string, slug: string, description: string, permissions: list<string>, staff: int}
     */
    public static function role(StoreRole $role): array
    {
        $permissions = [];

        foreach ($role->permissions ?? [] as $permission) {
            if (is_string($permission)) {
                $permissions[] = $permission;
            }
        }

        return [
            'name' => (string) $role->name,
            'slug' => (string) $role->slug,
            'description' => (string) ($role->description ?? ''),
            'permissions' => $permissions,
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
