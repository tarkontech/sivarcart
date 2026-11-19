<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Actions;

use Illuminate\Support\Facades\DB;
use SivarcartCore\SivarcartCore\Events\OrderCancelled;
use SivarcartCore\SivarcartCore\Models\Order;
use SivarcartCore\SivarcartCore\Services\OrderService;

/**
 * Cancels an order and gives the reserved units back.
 */
final class CancelOrder
{
    public function __construct(private readonly OrderService $orders)
    {
        //
    }

    /**
     * Cancel an order.
     */
    public function execute(Order $order, ?string $reason = null): Order
    {
        return DB::transaction(function () use ($order, $reason): Order {
            $cancelled = $this->orders->cancel($order, $reason);

            DB::afterCommit(fn () => event(new OrderCancelled($cancelled, $reason)));

            return $cancelled;
        });
    }
}
