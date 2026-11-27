<?php

declare(strict_types=1);

namespace Tarkontech\SivarCart\Actions;

use Illuminate\Support\Facades\DB;
use Tarkontech\SivarCart\Events\OrderCancelled;
use Tarkontech\SivarCart\Models\Order;
use Tarkontech\SivarCart\Services\OrderService;

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
