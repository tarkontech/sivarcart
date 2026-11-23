<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Actions;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\DB;
use SivarcartCore\SivarcartCore\Events\OrderReturned;
use SivarcartCore\SivarcartCore\Models\Order;
use SivarcartCore\SivarcartCore\Services\OrderService;

/**
 * Registers that an order came back to the store.
 *
 * The return does not move the stock: the units go back to the stock when the
 * order is refunded.
 */
final class ReturnOrder
{
    public function __construct(private readonly OrderService $orders)
    {
        //
    }

    /**
     * Register the return of an order.
     */
    public function execute(Order $order, ?Authenticatable $user = null, ?string $reason = null): Order
    {
        return DB::transaction(function () use ($order, $user, $reason): Order {
            $returned = $this->orders->markReturned($order, $user, $reason);

            DB::afterCommit(fn () => event(new OrderReturned($returned, $reason)));

            return $returned;
        });
    }
}
