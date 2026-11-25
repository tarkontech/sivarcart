<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Services\Admin;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use SivarcartCore\SivarcartCore\Models\Order;

/**
 * The orders of the store, as the staff of the store sees them.
 *
 * The service of the orders is the one of the shopper: it places an order and
 * moves it through its lifecycle. This one is the list the staff of the store
 * reads and filters.
 */
final class OrderService
{
    /**
     * The orders of the store.
     *
     * @return LengthAwarePaginator<int, Order>
     */
    public function paginate(string $search = '', string $status = '', int $perPage = 25): LengthAwarePaginator
    {
        return Order::query()
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('order_number', 'like', '%'.$search.'%')
                        ->orWhere('customer_name', 'like', '%'.$search.'%')
                        ->orWhere('customer_email', 'like', '%'.$search.'%');
                });
            })
            ->when($status !== '', function (Builder $query) use ($status): void {
                $query->where('status', $status);
            })
            ->orderByDesc('placed_at')
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    /**
     * One order of the store, by its uuid.
     */
    public function find(string $uuid): ?Order
    {
        return Order::query()
            ->with(['items', 'payments', 'statusHistories'])
            ->where('uuid', $uuid)
            ->first();
    }
}
