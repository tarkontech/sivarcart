<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Actions;

use SivarcartCore\SivarcartCore\Data\PaymentLinkResult;
use SivarcartCore\SivarcartCore\Enums\PaymentRecordStatus;
use SivarcartCore\SivarcartCore\Enums\PaymentStatus;
use SivarcartCore\SivarcartCore\Exceptions\PaymentGatewayFailed;
use SivarcartCore\SivarcartCore\Exceptions\SivarcartCoreException;
use SivarcartCore\SivarcartCore\Models\Order;
use SivarcartCore\SivarcartCore\Payments\PaymentGatewayManager;
use Throwable;

/**
 * Asks the gateway for the payment link of an order.
 *
 * The call to the gateway is never made inside a transaction: the order and
 * its reservation survive a failure, so the shopper can pay later.
 *
 * Once the link exists the payment of the order is pending, because the
 * shopper has not paid yet. A gateway that fails leaves the payment unpaid,
 * so the shopper can ask for the link again.
 */
final class StartPayment
{
    public function __construct(
        private readonly PaymentGatewayManager $gateways,
    ) {
        //
    }

    /**
     * Create the payment link of an order.
     *
     * @throws PaymentGatewayFailed
     * @throws SivarcartCoreException
     */
    public function execute(Order $order): PaymentLinkResult
    {
        $payment = $order->payments()
            ->where('status', PaymentRecordStatus::PENDING)
            ->first();

        if ($payment === null) {
            throw new SivarcartCoreException('La orden no tiene ningún pago pendiente.');
        }

        /** @var array<string, mixed> $meta */
        $meta = $payment->meta ?? [];
        $url = is_string($meta['payment_link_url'] ?? null) ? $meta['payment_link_url'] : null;

        if ($payment->gateway_reference !== null && $payment->gateway_reference !== '') {
            $this->markPaymentAsPending($order);

            return new PaymentLinkResult($payment->gateway_reference, $url, $meta);
        }

        $payment->increment('attempt_count');

        $gateway = $this->gateways->driver($payment->method?->driver);

        try {
            $result = $gateway->createPaymentLink($order, $payment);
        } catch (Throwable $exception) {
            throw PaymentGatewayFailed::from($exception);
        }

        $payment->gateway_reference = $result->reference;
        $payment->meta = array_merge($meta, [
            'payment_link_url' => $result->url,
            'payment_link_raw' => $result->raw,
        ]);
        $payment->save();

        $this->markPaymentAsPending($order);

        return $result;
    }

    /**
     * The link of the payment exists and the shopper has not paid yet, so the
     * payment of the order is pending.
     *
     * The payment is an axis of its own, so the transition is only made when
     * the axis allows it: a link of an order that was already paid does not
     * change what happened to it.
     */
    private function markPaymentAsPending(Order $order): void
    {
        if (! $order->payment_status->canTransitionTo(PaymentStatus::PENDING)) {
            return;
        }

        $order->transitionPaymentTo(PaymentStatus::PENDING);
    }
}
