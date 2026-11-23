<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Actions;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\DB;
use SivarcartCore\SivarcartCore\Enums\PaymentRecordStatus;
use SivarcartCore\SivarcartCore\Events\OrderPaid;
use SivarcartCore\SivarcartCore\Models\Order;
use SivarcartCore\SivarcartCore\Models\Payment;
use SivarcartCore\SivarcartCore\Services\OrderService;
use SivarcartCore\SivarcartCore\Services\PaymentService;

/**
 * Marks an order as paid without a gateway.
 *
 * This is the way the store confirms a payment it collected on its own, like
 * the cash of a delivery order. The units reserved for the order become a
 * sale and the order moves to the paid status.
 */
final class MarkOrderAsPaid
{
    public function __construct(
        private readonly OrderService $orders,
        private readonly PaymentService $payments,
    ) {
        //
    }

    /**
     * Mark an order as paid.
     */
    public function execute(Order $order, ?Payment $payment = null, ?Authenticatable $user = null): Order
    {
        return DB::transaction(function () use ($order, $payment, $user): Order {
            $target = $payment ?? $order->payments()
                ->where('status', PaymentRecordStatus::PENDING)
                ->first();

            if ($target !== null) {
                $this->payments->markPaid($target);
            }

            $this->orders->convertReservationToSale($order);
            $this->orders->markPaid($order, $user);

            DB::afterCommit(fn () => event(new OrderPaid($order, $target)));

            return $order;
        });
    }
}
