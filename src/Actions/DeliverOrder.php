<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Actions;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\DB;
use SivarcartCore\SivarcartCore\Events\OrderDelivered;
use SivarcartCore\SivarcartCore\Models\Order;
use SivarcartCore\SivarcartCore\Services\OrderService;

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
