<?php

declare(strict_types=1);

namespace Tarkontech\SivarCart\Tests\Support;

use RuntimeException;
use Tarkontech\SivarCart\Contracts\PaymentGateway;
use Tarkontech\SivarCart\Data\PaymentLinkResult;
use Tarkontech\SivarCart\Models\Order;
use Tarkontech\SivarCart\Models\Payment;

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
