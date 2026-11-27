<?php

declare(strict_types=1);

namespace Tarkontech\SivarCart\Actions;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\DB;
use Tarkontech\SivarCart\Events\OrderReturned;
use Tarkontech\SivarCart\Models\Order;
use Tarkontech\SivarCart\Services\OrderService;

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
