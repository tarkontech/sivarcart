<?php

declare(strict_types=1);

namespace Tarkontech\SivarCart\Http\Controllers\Storefront;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Tarkontech\SivarCart\Actions\PlaceOrder;
use Tarkontech\SivarCart\Actions\StartPayment;
use Tarkontech\SivarCart\Contracts\ShippingRateCalculator;
use Tarkontech\SivarCart\Data\CheckoutData;
use Tarkontech\SivarCart\Exceptions\PaymentGatewayFailed;
use Tarkontech\SivarCart\Exceptions\SivarCartException;
use Tarkontech\SivarCart\Http\Requests\CheckoutRequest;
use Tarkontech\SivarCart\Http\Requests\ShippingQuoteRequest;
use Tarkontech\SivarCart\Models\Customer;
use Tarkontech\SivarCart\Models\Order;
use Tarkontech\SivarCart\Models\ShippingMethod;
use Tarkontech\SivarCart\Services\CartService;
use Tarkontech\SivarCart\Services\CustomerService;
use Tarkontech\SivarCart\Services\DepartmentService;
use Tarkontech\SivarCart\Services\PaymentMethodService;
use Tarkontech\SivarCart\Services\ShippingMethodService;
use Tarkontech\SivarCart\Storefront\StorefrontPresenter;

/**
 * Takes the order of the shopper.
 *
 * The order is placed in a single transaction: the stock is reserved, the
 * order is created with the snapshot of what was bought and the cart is
 * closed. If any of that fails, nothing is written.
 */
final class CheckoutController
{
    public function __construct(
        private readonly CartService $carts,
        private readonly CustomerService $customers,
        private readonly PaymentMethodService $paymentMethods,
        private readonly ShippingMethodService $shippingMethods,
        private readonly DepartmentService $departments,
        private readonly ShippingRateCalculator $shipping,
        private readonly PlaceOrder $placeOrder,
        private readonly StartPayment $startPayment,
    ) {
        //
    }

    /**
     * The screen where the shopper places the order.
     */
    public function show(): Response
    {
        $buyer = $this->customers->user();

        $cart = $this->carts->findOrCreate((int) $buyer->getKey());

        $paymentMethods = [];

        foreach ($this->paymentMethods->available() as $method) {
            $paymentMethods[] = StorefrontPresenter::paymentMethod($method);
        }

        $shippingMethods = [];

        foreach ($this->shippingMethods->available() as $method) {
            $shippingMethods[] = StorefrontPresenter::shippingMethod($method);
        }

        $departments = [];

        foreach ($this->departments->available() as $department) {
            $departments[] = [
                'code' => (string) $department->code,
                'name' => (string) $department->name,
            ];
        }

        $address = $this->customers->defaultAddress($buyer);

        return Inertia::render('Storefront/Checkout/Index', [
            'cart' => StorefrontPresenter::cart($cart->load(['items', 'coupon'])),
            'payment_methods' => $paymentMethods,
            'shipping_methods' => $shippingMethods,
            'departments' => $departments,
            'buyer' => StorefrontPresenter::customer($buyer),
            'address' => $address === null ? null : $this->customers->addressToArray($address),
        ]);
    }

    /**
     * How much the shipping of the cart costs.
     */
    public function shippingQuote(ShippingQuoteRequest $request): JsonResponse
    {
        $buyer = $this->customers->user();

        $cart = $this->carts->findOrCreate((int) $buyer->getKey());

        $code = (string) $request->validated('shipping_method_code');

        $method = $this->shippingMethods->findByCode($code);

        if (! $method instanceof ShippingMethod) {
            throw new SivarCartException(sprintf('El método de envío "%s" no está disponible.', $code));
        }

        $quote = $this->shipping->quote($method, $cart, (array) $request->validated('address'));

        return response()->json([
            'amount' => $quote->amount,
            'estimated_days' => $quote->estimatedDays,
        ]);
    }

    /**
     * Place the order of the cart of the shopper.
     */
    public function store(CheckoutRequest $request): RedirectResponse
    {
        $buyer = $this->customers->user();

        $cart = $this->carts->findOrCreate((int) $buyer->getKey());

        $couponCode = $this->text($request->validated('coupon_code'));

        if ($couponCode !== null) {
            // The order reads the coupon of the cart, so the code the shopper
            // typed is applied to the cart before the order is placed.
            $this->carts->applyCoupon($cart, $couponCode);
        }

        try {
            $order = $this->placeOrder->execute($cart, new CheckoutData(
                customerName: (string) $request->validated('customer_name'),
                customerEmail: $this->text($request->validated('customer_email')),
                customerPhone: $this->text($request->validated('customer_phone')),
                shippingAddress: (array) $request->validated('shipping_address'),
                billingAddress: $this->address($request->validated('billing_address')),
                shippingMethodCode: $this->text($request->validated('shipping_method_code')),
                paymentMethodCode: $this->text($request->validated('payment_method_code')),
                couponCode: $couponCode,
                customerNote: $this->text($request->validated('customer_note')),
            ));
        } catch (SivarCartException $exception) {
            return back()->withErrors(['checkout' => $exception->getMessage()]);
        }

        $this->saveAddress($buyer, $order);

        [$paymentLink, $paymentLinkError] = $this->paymentLink($order);

        return redirect()
            ->route('sivarcart.storefront.orders.show', ['order' => $order->uuid])
            ->with('payment_link', $paymentLink)
            ->with('payment_link_error', $paymentLinkError);
    }

    /**
     * The link the shopper pays with, when the method is paid online.
     *
     * The link is asked for after the order is confirmed: the order and its
     * reservation survive a gateway that fails, and the shopper can ask for
     * the link again.
     *
     * @return array{0: array<string, mixed>|null, 1: string|null}
     */
    private function paymentLink(Order $order): array
    {
        $payment = $order->payments()->first();

        if ($payment === null || ! $payment->method?->isOnline()) {
            return [null, null];
        }

        try {
            $result = $this->startPayment->execute($order);
        } catch (PaymentGatewayFailed $exception) {
            return [null, $exception->getMessage()];
        }

        return [
            [
                'url' => $result->url,
                'reference' => $result->reference,
            ],
            null,
        ];
    }

    /**
     * Save the address the order was delivered to.
     *
     * The address of la orden es un arreglo, así que se traduce en la fila
     * del comprador para que la próxima vez ya esté escrita.
     */
    private function saveAddress(Customer $buyer, Order $order): void
    {
        $address = $order->shipping_address;

        if (! is_array($address) || $address === []) {
            return;
        }

        $this->customers->saveAddress($buyer, $address);
    }

    /**
     * A text of the request, when it has one.
     */
    private function text(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }

    /**
     * An address of the request, when it has one.
     *
     * @return array<string, mixed>|null
     */
    private function address(mixed $value): ?array
    {
        if (! is_array($value)) {
            return null;
        }

        return $value;
    }
}
