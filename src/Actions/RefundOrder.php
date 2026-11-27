<?php

declare(strict_types=1);

namespace Tarkontech\SivarCart\Actions;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\DB;
use Tarkontech\SivarCart\Events\OrderRefunded;
use Tarkontech\SivarCart\Models\Order;
use Tarkontech\SivarCart\Services\OrderService;

/**
 * Registers the refund of an order.
 *
 * The Wompi API does not offer a way to refund a payment, so the core
 * records the refund and gives the units back. The store applies the refund
 * on the Wompi dashboard.
 */
final class RefundOrder
{
    public function __construct(private readonly OrderService $orders)
    {
        //
    }

    /**
     * Refund an order.
     */
    public function execute(Order $order, float $amount, ?string $reason = null, ?Authenticatable $user = null): Order
    {
        return DB::transaction(function () use ($order, $amount, $reason, $user): Order {
            $refunded = $this->orders->refund($order, $amount, $reason, $user);

            DB::afterCommit(fn () => event(new OrderRefunded($refunded, $amount, $reason)));

            return $refunded;
        });
    }
}
