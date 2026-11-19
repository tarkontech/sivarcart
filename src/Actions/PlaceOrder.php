<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Actions;

use Illuminate\Support\Facades\DB;
use SivarcartCore\SivarcartCore\Data\CheckoutData;
use SivarcartCore\SivarcartCore\Events\OrderPlaced;
use SivarcartCore\SivarcartCore\Models\Cart;
use SivarcartCore\SivarcartCore\Models\Order;
use SivarcartCore\SivarcartCore\Services\OrderService;

/**
 * Turns the cart of a shopper into an order.
 */
final class PlaceOrder
{
    public function __construct(private readonly OrderService $orders)
    {
        //
    }

    /**
     * Place the order of a cart.
     *
     * Everything happens in a single transaction: the stock is reserved, the
     * order is created with the snapshot of what was bought and the cart is
     * closed. If any of that fails, nothing is written.
     */
    public function execute(Cart $cart, CheckoutData $data): Order
    {
        return DB::transaction(function () use ($cart, $data): Order {
            $order = $this->orders->place($cart, $data);

            DB::afterCommit(fn () => event(new OrderPlaced($order)));

            return $order;
        });
    }
}
