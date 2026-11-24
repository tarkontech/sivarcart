<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use SivarcartCore\SivarcartCore\Actions\PlaceOrder;
use SivarcartCore\SivarcartCore\Actions\StartPayment;
use SivarcartCore\SivarcartCore\Contracts\ShippingRateCalculator;
use SivarcartCore\SivarcartCore\Data\CheckoutData;
use SivarcartCore\SivarcartCore\Exceptions\PaymentGatewayFailed;
use SivarcartCore\SivarcartCore\Exceptions\SivarcartCoreException;
use SivarcartCore\SivarcartCore\Http\Requests\CheckoutRequest;
use SivarcartCore\SivarcartCore\Http\Requests\ShippingQuoteRequest;
use SivarcartCore\SivarcartCore\Http\Resources\OrderResource;
use SivarcartCore\SivarcartCore\Http\Resources\PaymentMethodResource;
use SivarcartCore\SivarcartCore\Http\Resources\ShippingMethodResource;
use SivarcartCore\SivarcartCore\Models\Department;
use SivarcartCore\SivarcartCore\Models\Order;
use SivarcartCore\SivarcartCore\Services\CartService;
use SivarcartCore\SivarcartCore\Services\DepartmentService;
use SivarcartCore\SivarcartCore\Services\PaymentMethodService;
use SivarcartCore\SivarcartCore\Services\ShippingMethodService;
use SivarcartCore\SivarcartCore\Services\StoreService;

/**
 * Takes the order of a shopper.
 *
 * The order is placed in a single transaction: the stock is reserved, the
 * order is created with the snapshot of what was bought and the cart is
 * closed. If any of that fails, nothing is written.
 */
final class CheckoutController extends ApiController
{
    public function __construct(
        private readonly CartService $carts,
        private readonly PaymentMethodService $paymentMethods,
        private readonly ShippingMethodService $shippingMethods,
        private readonly DepartmentService $departments,
        private readonly StoreService $stores,
        private readonly ShippingRateCalculator $shipping,
        private readonly PlaceOrder $placeOrder,
        private readonly StartPayment $startPayment,
    ) {
        //
    }

    /**
     * The options the shopper picks from: how to pay, how to receive the
     * order and where to deliver it.
     */
    public function options(): JsonResponse
    {
        $store = $this->stores->current();

        return response()->json([
            'store' => [
                'name' => $store?->name,
                'currency' => $this->stores->currency(),
                'timezone' => $this->stores->timezone(),
                'prices_include_tax' => $this->stores->pricesIncludeTax(),
                'default_tax_rate' => $this->stores->defaultTaxRate(),
            ],
            'payment_methods' => PaymentMethodResource::collection($this->paymentMethods->available())->resolve(),
            'shipping_methods' => ShippingMethodResource::collection($this->shippingMethods->available())->resolve(),
            'departments' => $this->departments->available()
                ->map(fn (Department $department): array => [
                    'code' => $department->code,
                    'name' => $department->name,
                ])
                ->all(),
        ]);
    }

    /**
     * How much the shipping of the cart costs.
     */
    public function shippingQuote(ShippingQuoteRequest $request): JsonResponse
    {
        $buyer = $this->buyer();

        $cart = $this->carts->findOrCreate((int) $buyer->getKey());

        $code = (string) $request->validated('shipping_method_code');

        $method = $this->shippingMethods->findByCode($code);

        if ($method === null) {
            throw new SivarcartCoreException(sprintf('El método de envío "%s" no está disponible.', $code));
        }

        $quote = $this->shipping->quote($method, $cart, (array) $request->validated('address'));

        return response()->json([
            'amount' => $quote->amount,
            'estimated_days' => $quote->estimatedDays,
        ]);
    }

    /**
     * Place the order of the cart of the buyer.
     */
    public function store(CheckoutRequest $request): JsonResponse
    {
        $buyer = $this->buyer();

        $cart = $this->carts->findOrCreate((int) $buyer->getKey());

        $couponCode = $this->text($request->validated('coupon_code'));

        if ($couponCode !== null) {
            // The order reads the coupon of the cart, so the code the shopper
            // typed is applied to the cart before the order is placed.
            $this->carts->applyCoupon($cart, $couponCode);
        }

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

        [$paymentLink, $paymentLinkError] = $this->paymentLink($order);

        return OrderResource::make($order->load(['items', 'payments']))
            ->additional([
                'payment_link' => $paymentLink,
                'payment_link_error' => $paymentLinkError,
            ])
            ->response()
            ->setStatusCode(201);
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
