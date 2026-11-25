<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Actions;

use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Rmirandasv\Wompi\Webhooks\WompiWebhook;
use SivarcartCore\SivarcartCore\Enums\PaymentStatus;
use SivarcartCore\SivarcartCore\Events\OrderPaid;
use SivarcartCore\SivarcartCore\Events\PaymentFailed;
use SivarcartCore\SivarcartCore\Models\Order;
use SivarcartCore\SivarcartCore\Models\Payment;
use SivarcartCore\SivarcartCore\Models\PaymentTransaction;
use SivarcartCore\SivarcartCore\Services\OrderService;
use SivarcartCore\SivarcartCore\Services\PaymentService;

/**
 * Applies the payment Wompi reported.
 *
 * Wompi delivers the same webhook more than once when it does not get an
 * answer, so the delivery is identified by the idempotency key of the
 * transaction: the second delivery of the same payment is ignored.
 *
 * The payment of the order follows the payment that was reported: the order
 * is paid when the transaction was approved and failed when it was not.
 */
final class HandleWompiPayment
{
    public function __construct(
        private readonly PaymentService $payments,
        private readonly OrderService $orders,
    ) {
        //
    }

    /**
     * Apply the payment of a webhook.
     *
     * A payment is only settled when the transaction was approved *and* it
     * was a real charge: a transaction of test never billed the shopper.
     */
    public function execute(WompiWebhook $webhook): void
    {
        $reference = $webhook->reference();

        if ($reference === null || $reference === '') {
            Log::warning('A Wompi webhook arrived without a merchant reference.', [
                'transaction' => $webhook->idTransaccion(),
            ]);

            return;
        }

        $payment = Payment::query()->where('reference', $reference)->first();

        if ($payment === null) {
            Log::warning('A Wompi webhook arrived for an unknown payment.', [
                'reference' => $reference,
            ]);

            return;
        }

        $order = $payment->order;

        if ($order === null) {
            Log::warning('A Wompi webhook arrived for a payment without an order.', [
                'reference' => $reference,
            ]);

            return;
        }

        if (PaymentTransaction::query()->where('idempotency_key', $webhook->idempotencyKey())->exists()) {
            return;
        }

        try {
            DB::transaction(function () use ($payment, $order, $webhook): void {
                $this->payments->recordTransaction($payment, $webhook);

                if ($webhook->isSuccessful()) {
                    $this->payments->markPaid($payment, $webhook->idTransaccion());
                    $this->orders->convertReservationToSale($order);
                    $this->orders->markPaid($order);

                    DB::afterCommit(fn () => event(new OrderPaid($order, $payment)));

                    return;
                }

                $reason = $this->reasonFor($webhook);

                $this->payments->markFailed($payment, $reason);

                $this->markPaymentAsFailed($order);

                DB::afterCommit(fn () => event(new PaymentFailed($order, $payment, $reason)));
            });
        } catch (UniqueConstraintViolationException) {
            Log::notice('Two deliveries of the same Wompi webhook arrived at the same time.', [
                'reference' => $reference,
            ]);
        }
    }

    /**
     * Why the payment was not settled.
     */
    private function reasonFor(WompiWebhook $webhook): string
    {
        if (! $webhook->isApproved()) {
            return 'La pasarela no aprobó la transacción.';
        }

        return 'La transacción fue una prueba y nunca se cobró al comprador.';
    }

    /**
     * The payment of the order failed.
     *
     * The payment is an axis of its own and it does not go back: an order that
     * was already paid keeps its paid payment, because the money arrived. The
     * guard leaves the order alone when the axis does not allow the
     * transition, so a webhook that arrives late does not break the delivery.
     */
    private function markPaymentAsFailed(Order $order): void
    {
        if (! $order->payment_status->canTransitionTo(PaymentStatus::FAILED)) {
            return;
        }

        $order->transitionPaymentTo(PaymentStatus::FAILED);
    }
}
