<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Data;

/**
 * Everything the shopper provides when the order is placed.
 */
final class CheckoutData
{
    /**
     * @param  string  $customerName  The name of the shopper, required for the receipt.
     * @param  string|null  $customerEmail  The email of the shopper, optional for a guest checkout.
     * @param  string|null  $customerPhone  The phone of the shopper.
     * @param  array<string, mixed>  $shippingAddress  Where the order has to be delivered.
     * @param  array<string, mixed>|null  $billingAddress  Where the receipt has to be sent.
     * @param  string|null  $shippingMethodCode  The code of the chosen shipping method.
     * @param  string|null  $paymentMethodCode  The code of the chosen payment method.
     * @param  string|null  $couponCode  The discount code the shopper typed.
     * @param  string|null  $customerNote  A note the shopper left for the store.
     * @param  int|null  $customerId  The customer the order belongs to, null for a guest.
     */
    public function __construct(
        public readonly string $customerName,
        public readonly ?string $customerEmail = null,
        public readonly ?string $customerPhone = null,
        public readonly array $shippingAddress = [],
        public readonly ?array $billingAddress = null,
        public readonly ?string $shippingMethodCode = null,
        public readonly ?string $paymentMethodCode = null,
        public readonly ?string $couponCode = null,
        public readonly ?string $customerNote = null,
        public readonly ?int $customerId = null,
    ) {
        //
    }
}
