<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Data;

/**
 * The payment link a gateway created for an order.
 */
final class PaymentLinkResult
{
    /**
     * @param  string  $reference  The identifier of the link on the gateway.
     * @param  string|null  $url  Where the shopper has to be sent to pay.
     * @param  array<string, mixed>  $raw  The payload the gateway answered.
     */
    public function __construct(
        public readonly string $reference,
        public readonly ?string $url = null,
        public readonly array $raw = [],
    ) {
        //
    }
}
