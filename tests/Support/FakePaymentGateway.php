<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Tests\Support;

use RuntimeException;
use SivarcartCore\SivarcartCore\Contracts\PaymentGateway;
use SivarcartCore\SivarcartCore\Data\PaymentLinkResult;
use SivarcartCore\SivarcartCore\Models\Order;
use SivarcartCore\SivarcartCore\Models\Payment;

/**
 * A payment gateway that never talks to the internet.
 */
final class FakePaymentGateway implements PaymentGateway
{
    /**
     * How many times the gateway was asked for a link.
     */
    public int $calls = 0;

    /**
     * Whether the gateway refuses to work.
     */
    public bool $failing = false;

    public function createPaymentLink(Order $order, Payment $payment): PaymentLinkResult
    {
        $this->calls++;

        if ($this->failing) {
            throw new RuntimeException('La pasarela no respondió.');
        }

        return new PaymentLinkResult(
            reference: 'enlace-'.$payment->reference,
            url: 'https://pay.example.test/'.$payment->reference,
            raw: ['idEnlace' => 'enlace-'.$payment->reference],
        );
    }
}
