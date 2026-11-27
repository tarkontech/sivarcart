<?php

declare(strict_types=1);

namespace Tarkontech\SivarCart\Actions;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\DB;
use Tarkontech\SivarCart\Events\OrderDelivered;
use Tarkontech\SivarCart\Models\Order;
use Tarkontech\SivarCart\Services\OrderService;

/**
 * Marks an order as delivered.
 */
final class DeliverOrder
{
    public function __construct(private readonly OrderService $orders)
    {
        //
    }

    /**
     * Deliver an order.
     */
    public function execute(Order $order, ?Authenticatable $user = null, ?string $note = null): Order
    {
        return DB::transaction(function () use ($order, $user, $note): Order {
            $delivered = $this->orders->deliver($order, $user, $note);

            DB::afterCommit(fn () => event(new OrderDelivered($delivered)));

            return $delivered;
        });
    }
}
