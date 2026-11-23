<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Actions;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\DB;
use SivarcartCore\SivarcartCore\Events\OrderShipped;
use SivarcartCore\SivarcartCore\Models\Order;
use SivarcartCore\SivarcartCore\Services\OrderService;

/**
 * Ships an order.
 *
 * The order may still be unpaid: an order paid on delivery leaves the store
 * before the money arrives.
 */
final class ShipOrder
{
    public function __construct(private readonly OrderService $orders)
    {
        //
    }

    /**
     * Ship an order.
     */
    public function execute(Order $order, ?Authenticatable $user = null, ?string $note = null): Order
    {
        return DB::transaction(function () use ($order, $user, $note): Order {
            $shipped = $this->orders->ship($order, $user, $note);

            DB::afterCommit(fn () => event(new OrderShipped($shipped)));

            return $shipped;
        });
    }
}
