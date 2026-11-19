<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Actions;

use SivarcartCore\SivarcartCore\Data\PaymentLinkResult;
use SivarcartCore\SivarcartCore\Enums\PaymentRecordStatus;
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

        return $result;
    }
}
