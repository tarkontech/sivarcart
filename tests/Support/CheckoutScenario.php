<?php

declare(strict_types=1);

namespace Tarkontech\SivarCart\Tests\Support;

use Tarkontech\SivarCart\Data\CheckoutData;
use Tarkontech\SivarCart\Models\Cart;
use Tarkontech\SivarCart\Models\Customer;
use Tarkontech\SivarCart\Models\Product;
use Tarkontech\SivarCart\Models\ProductVariant;
use Tarkontech\SivarCart\Models\Store;
use Tarkontech\SivarCart\Services\CartService;

/**
 * A store with a cart that holds one unit of a product that costs 25.50.
 *
 * The scenarios keep the tests focused on the behaviour they are about: they
 * only build what every checkout needs.
 */
final class CheckoutScenario
{
    private function __construct(
        public readonly Cart $cart,
        public readonly ProductVariant $variant,
        public readonly CheckoutData $data,
        public readonly Customer $customer,
    ) {
        //
    }

    /**
     * Build a scenario for a buyer that just signed up.
     */
    public static function buyer(
        ?string $shippingMethodCode = null,
        ?string $paymentMethodCode = null,
        int $quantity = 1,
    ): self {
        return self::forCustomer(
            Customer::factory()->create(),
            $shippingMethodCode,
            $paymentMethodCode,
            $quantity,
        );
    }

    /**
     * Build a scenario for a buyer that already has an account.
     */
    public static function forCustomer(
        Customer $customer,
        ?string $shippingMethodCode = null,
        ?string $paymentMethodCode = null,
        int $quantity = 1,
    ): self {
        Store::factory()->create();

        $variant = Product::factory()->create()->defaultVariant();
        $cart = Cart::factory()->create(['customer_id' => $customer->getKey()]);

        app(CartService::class)->addItem($cart, $variant, $quantity);

        return new self(
            cart: $cart->fresh(),
            variant: $variant,
            data: new CheckoutData(
                customerName: 'Ana Guzmán',
                customerEmail: $customer->email,
                customerPhone: $customer->phone,
                shippingAddress: [
                    'first_name' => 'Ana',
                    'last_name' => 'Guzmán',
                    'address_line1' => 'Calle 1',
                    'municipality' => 'San Salvador',
                    'department' => 'San Salvador',
                ],
                shippingMethodCode: $shippingMethodCode,
                paymentMethodCode: $paymentMethodCode,
            ),
            customer: $customer,
        );
    }
}
