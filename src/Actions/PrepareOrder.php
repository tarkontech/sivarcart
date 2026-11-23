<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Actions;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\DB;
use SivarcartCore\SivarcartCore\Events\OrderPrepared;
use SivarcartCore\SivarcartCore\Models\Order;
use SivarcartCore\SivarcartCore\Services\OrderService;

/**
 * Starts the preparation of an order.
 *
 * The stock does not move: the units were reserved when the order was placed
 * and they become a sale when the order is paid.
 */
final class PrepareOrder
{
    public function __construct(private readonly OrderService $orders)
    {
        //
    }

    /**
     * Prepare an order.
     */
    public function execute(Order $order, ?Authenticatable $user = null, ?string $note = null): Order
    {
        return DB::transaction(function () use ($order, $user, $note): Order {
            $prepared = $this->orders->prepare($order, $user, $note);

            DB::afterCommit(fn () => event(new OrderPrepared($prepared)));

            return $prepared;
        });
    }
}
