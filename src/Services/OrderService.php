<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Log;
use SivarcartCore\SivarcartCore\Contracts\ShippingRateCalculator;
use SivarcartCore\SivarcartCore\Data\CheckoutData;
use SivarcartCore\SivarcartCore\Data\ShippingQuote;
use SivarcartCore\SivarcartCore\Enums\DocumentSequenceType;
use SivarcartCore\SivarcartCore\Enums\OrderStatus;
use SivarcartCore\SivarcartCore\Enums\PaymentRecordStatus;
use SivarcartCore\SivarcartCore\Enums\PaymentStatus;
use SivarcartCore\SivarcartCore\Exceptions\CustomerRequired;
use SivarcartCore\SivarcartCore\Exceptions\InsufficientStock;
use SivarcartCore\SivarcartCore\Exceptions\InvalidCartState;
use SivarcartCore\SivarcartCore\Exceptions\SivarcartCoreException;
use SivarcartCore\SivarcartCore\Models\Cart;
use SivarcartCore\SivarcartCore\Models\CartItem;
use SivarcartCore\SivarcartCore\Models\Customer;
use SivarcartCore\SivarcartCore\Models\Order;
use SivarcartCore\SivarcartCore\Models\PaymentMethod;
use SivarcartCore\SivarcartCore\Models\ShippingMethod;

/**
 * Keeps the orders of the store.
 *
 * An order is a snapshot: the name, the price and the tax rate of every item
 * are frozen when the order is placed, so editing the catalog never rewrites
 * the history of an order that already exists.
 *
 * @phpstan-import-type PriceLineInput from PricingService
 * @phpstan-import-type PriceLineResult from PricingService
 */
final class OrderService
{
    public function __construct(
        private readonly CartService $carts,
        private readonly CouponService $coupons,
        private readonly CustomerService $customers,
        private readonly PricingService $pricing,
        private readonly InventoryService $inventory,
        private readonly PaymentService $payments,
        private readonly SequenceService $sequences,
        private readonly StoreService $stores,
        private readonly ShippingRateCalculator $shipping,
    ) {
        //
    }

    /**
     * Turn a cart into an order.
     *
     * The stock is reserved, the order and its items are created with the
     * snapshot of what was bought and the cart is closed.
     *
     * The buyer of the order is the one of the cart: a cart without a buyer
     * cannot become an order, because the buyer signs in before buying.
     *
     * @throws CustomerRequired
     * @throws InvalidCartState
     * @throws SivarcartCoreException
     */
    public function place(Cart $cart, CheckoutData $data): Order
    {
        $locked = Cart::query()
            ->whereKey($cart->getKey())
            ->lockForUpdate()
            ->firstOrFail();

        if ($locked->isConverted()) {
            throw InvalidCartState::alreadyConverted($locked);
        }

        $customer = $this->buyer($locked);

        $items = $locked->items()->get();

        if ($items->isEmpty()) {
            throw InvalidCartState::empty();
        }

        $this->carts->recalculate($locked);

        $coupon = $locked->coupon;
        $lines = $this->carts->lines($locked);
        $subtotal = $this->subtotal($lines);
        $taxInclusive = $this->stores->pricesIncludeTax();

        if ($coupon !== null) {
            $this->coupons->assertApplicable($coupon, $subtotal, $locked->customer_id);
        }

        $discount = $coupon === null ? 0.0 : $this->coupons->discountFor($coupon, $subtotal);

        $shippingAddress = $this->shippingAddress($customer, $data);

        $shippingMethod = $this->shippingMethod($data, $subtotal);
        $shipping = $shippingMethod === null
            ? new ShippingQuote(0.0)
            : $this->shipping->quote($shippingMethod, $locked, $shippingAddress);

        $shippingDiscount = $coupon === null
            ? 0.0
            : $this->coupons->shippingDiscountFor($coupon, $shipping->amount);

        $priced = $this->pricing->price($lines, $taxInclusive, $discount);
        $totals = $this->pricing->totals($priced, $shipping->amount, $shippingDiscount, $taxInclusive);

        $order = Order::query()->create([
            'order_number' => $this->sequences->next(
                DocumentSequenceType::ORDER,
                $this->stores->orderNumberPrefix(),
            ),
            'customer_id' => $customer->getKey(),
            'customer_name' => $data->customerName,
            'customer_email' => $data->customerEmail ?? $customer->email,
            'customer_phone' => $data->customerPhone ?? $customer->phone,
            'status' => OrderStatus::PENDING,
            'payment_status' => PaymentStatus::UNPAID,
            'currency' => $locked->currency,
            'subtotal' => $totals->subtotal,
            'discount_total' => $totals->discountTotal,
            'tax_total' => $totals->taxTotal,
            'shipping_total' => $totals->shippingTotal,
            'total' => $totals->total,
            'tax_inclusive' => $totals->taxInclusive,
            'coupon_code' => $coupon?->code,
            'shipping_address' => $shippingAddress,
            'billing_address' => $data->billingAddress,
            'shipping_method_code' => $shippingMethod?->code,
            'shipping_method_name' => $shippingMethod?->name,
            'customer_note' => $data->customerNote,
            'placed_at' => now(),
        ]);

        $this->createItems($order, $items, $lines, $priced);

        $this->reserveStock($order, $items);

        $paymentMethod = $this->paymentMethod($data);

        if ($paymentMethod !== null) {
            $this->payments->createPending($order, $paymentMethod);
        }

        $locked->converted_at = now();
        $locked->shipping_total = $totals->shippingTotal;
        $locked->total = $totals->total;
        $locked->save();

        if ($coupon !== null) {
            $coupon->increment('used_count');
        }

        $this->customers->recordOrder($customer);

        return $order;
    }

    /**
     * The buyer of the order.
     *
     * @throws CustomerRequired
     */
    private function buyer(Cart $cart): Customer
    {
        $customerId = $cart->customer_id;

        if ($customerId === null) {
            throw CustomerRequired::forCart($cart);
        }

        $customer = Customer::query()->find($customerId);

        if ($customer === null) {
            throw CustomerRequired::forCart($cart);
        }

        return $customer;
    }

    /**
     * Where the order has to be delivered.
     *
     * The address of the checkout is saved for the buyer, so the next order
     * starts from the address the buyer already has.
     *
     * @return array<string, mixed>
     */
    private function shippingAddress(Customer $customer, CheckoutData $data): array
    {
        if ($data->shippingAddress === []) {
            $saved = $this->customers->defaultAddress($customer);

            return $saved === null ? [] : $this->customers->addressToArray($saved);
        }

        $this->customers->saveAddress($customer, $data->shippingAddress);

        return $data->shippingAddress;
    }

    /**
     * Move the payment of an order to the paid state.
     *
     * The payment is an axis of its own: it does not move the order. An order
     * that is paid and not prepared yet stays pending, and the change is
     * recorded on the axis of the payment.
     */
    public function markPaid(Order $order, ?Authenticatable $user = null): Order
    {
        $order->transitionPaymentTo(PaymentStatus::PAID, $user);

        return $order;
    }

    /**
     * Start the preparation of an order.
     */
    public function prepare(Order $order, ?Authenticatable $user = null, ?string $note = null): Order
    {
        $order->transitionTo(OrderStatus::PROCESSING, $user, $note);

        return $order;
    }

    /**
     * Ship an order.
     *
     * The order does not have to be paid: an order paid on delivery leaves
     * the store before the money arrives.
     */
    public function ship(Order $order, ?Authenticatable $user = null, ?string $note = null): Order
    {
        $order->transitionTo(OrderStatus::SHIPPED, $user, $note);

        return $order;
    }

    /**
     * Mark an order as delivered.
     */
    public function deliver(Order $order, ?Authenticatable $user = null, ?string $note = null): Order
    {
        $order->transitionTo(OrderStatus::DELIVERED, $user, $note);

        return $order;
    }

    /**
     * Register that an order came back to the store.
     *
     * The units do not go back to the stock yet: they do when the order is
     * refunded.
     */
    public function markReturned(Order $order, ?Authenticatable $user = null, ?string $reason = null): Order
    {
        $order->transitionTo(OrderStatus::RETURNED, $user, $reason);

        return $order;
    }

    /**
     * Turn the units reserved for an order into a sale.
     */
    public function convertReservationToSale(Order $order): void
    {
        foreach ($order->items()->get() as $item) {
            $variant = $item->variant;

            if ($variant === null) {
                continue;
            }

            try {
                $this->inventory->sell(
                    $variant,
                    (int) $item->quantity,
                    'Orden '.$order->order_number.' pagada',
                    Order::class,
                    $order->getKey(),
                );
            } catch (InsufficientStock $exception) {
                Log::warning('The stock of an order could not be sold.', [
                    'order' => $order->order_number,
                    'reason' => $exception->getMessage(),
                ]);
            }
        }
    }

    /**
     * Cancel an order and give the reserved units back.
     *
     * The payment of the order is cancelled with it, unless the money was
     * already collected.
     */
    public function cancel(Order $order, ?string $reason = null): Order
    {
        $order->transitionTo(OrderStatus::CANCELLED, note: $reason);

        foreach ($order->items()->get() as $item) {
            $variant = $item->variant;

            if ($variant === null) {
                continue;
            }

            $this->inventory->release(
                $variant,
                (int) $item->quantity,
                'Orden '.$order->order_number.' cancelada',
                Order::class,
                $order->getKey(),
            );
        }

        foreach ($order->payments()->get() as $payment) {
            if ($payment->status === PaymentRecordStatus::PENDING) {
                $this->payments->cancel($payment);
            }
        }

        $this->markPaymentAsCancelled($order);

        return $order;
    }

    /**
     * The payment of a cancelled order is cancelled too.
     *
     * An order that was paid keeps its paid payment: the money was collected,
     * and giving it back is the job of the refund. The transition is only
     * made when the axis of the payment allows it.
     */
    private function markPaymentAsCancelled(Order $order): void
    {
        if (! $order->payment_status->canTransitionTo(PaymentStatus::CANCELLED)) {
            return;
        }

        $order->transitionPaymentTo(PaymentStatus::CANCELLED);
    }

    /**
     * Refund an order, give the units back and close it.
     */
    public function refund(Order $order, float $amount, ?string $reason = null, ?Authenticatable $user = null): Order
    {
        if ($amount <= 0) {
            throw new SivarcartCoreException('El monto del reembolso tiene que ser mayor que cero.');
        }

        $payment = $order->payments()->first();

        if ($payment === null) {
            throw new SivarcartCoreException('La orden no tiene ningún pago que reembolsar.');
        }

        $available = round((float) $payment->amount - (float) $payment->refunded_amount, 2);

        if ($amount > $available) {
            throw new SivarcartCoreException(sprintf(
                'El reembolso no puede ser mayor que %s.',
                number_format($available, 2),
            ));
        }

        $this->payments->refund($payment, $amount);

        $refunded = round((float) $payment->refunded_amount, 2);
        $isTotal = $refunded >= (float) $payment->amount;

        $order->transitionPaymentTo(
            $isTotal ? PaymentStatus::REFUNDED : PaymentStatus::PARTIALLY_REFUNDED,
            $user,
        );

        $this->returnStock($order, $amount);

        if ($isTotal && $order->status->canTransitionTo(OrderStatus::REFUNDED)) {
            $order->transitionTo(OrderStatus::REFUNDED, $user, $reason);
        }

        return $order;
    }

    /**
     * Create the items of the order with the snapshot of what was bought.
     *
     * @param  iterable<CartItem>  $items
     * @param  list<PriceLineInput>  $lines
     * @param  list<PriceLineResult>  $priced
     */
    private function createItems(Order $order, iterable $items, array $lines, array $priced): void
    {
        $index = 0;

        foreach ($items as $item) {
            $order->items()->create([
                'product_id' => $item->product_id,
                'product_variant_id' => $item->product_variant_id,
                'name' => $item->name,
                'sku' => $item->sku,
                'variant_options' => $item->options,
                'unit_price' => (float) $item->unit_price,
                'quantity' => (int) $item->quantity,
                'line_subtotal' => $priced[$index]['line_subtotal'],
                'discount_total' => $priced[$index]['discount_total'],
                'tax_rate' => $lines[$index]['tax_rate'],
                'tax_total' => $priced[$index]['tax_total'],
                'line_total' => $priced[$index]['line_total'],
            ]);

            $index++;
        }
    }

    /**
     * Reserve the units the order is buying.
     *
     * @param  iterable<CartItem>  $items
     */
    private function reserveStock(Order $order, iterable $items): void
    {
        foreach ($items as $item) {
            $variant = $item->variant;

            if ($variant === null) {
                continue;
            }

            $this->inventory->reserve(
                $variant,
                (int) $item->quantity,
                'Orden '.$order->order_number,
                Order::class,
                $order->getKey(),
            );
        }
    }

    /**
     * Give the units of a refunded order back to the stock.
     */
    private function returnStock(Order $order, float $amount): void
    {
        $total = (float) $order->total;

        if ($total <= 0) {
            return;
        }

        foreach ($order->items()->get() as $item) {
            $variant = $item->variant;
            $unitPrice = (float) $item->unit_price;

            if ($variant === null || $unitPrice <= 0) {
                continue;
            }

            $share = ((float) $item->line_total / $total) * $amount;
            $units = min((int) floor($share / $unitPrice), (int) $item->quantity);

            if ($units < 1) {
                continue;
            }

            $this->inventory->returnToStock(
                $variant,
                $units,
                'Reembolso de la orden '.$order->order_number,
                Order::class,
                $order->getKey(),
            );
        }
    }

    /**
     * The shipping method the shopper chose.
     *
     * @throws SivarcartCoreException
     */
    private function shippingMethod(CheckoutData $data, float $subtotal): ?ShippingMethod
    {
        if ($data->shippingMethodCode === null || $data->shippingMethodCode === '') {
            return null;
        }

        $method = ShippingMethod::query()
            ->where('code', $data->shippingMethodCode)
            ->where('is_active', true)
            ->first();

        if ($method === null) {
            throw new SivarcartCoreException(sprintf(
                'El método de envío "%s" no está disponible.',
                $data->shippingMethodCode,
            ));
        }

        if (! $method->appliesTo($subtotal)) {
            throw new SivarcartCoreException(sprintf(
                'El método de envío "%s" no aplica a esta orden.',
                $method->code,
            ));
        }

        return $method;
    }

    /**
     * The payment method the shopper chose.
     *
     * @throws SivarcartCoreException
     */
    private function paymentMethod(CheckoutData $data): ?PaymentMethod
    {
        if ($data->paymentMethodCode === null || $data->paymentMethodCode === '') {
            return null;
        }

        $method = PaymentMethod::query()
            ->where('code', $data->paymentMethodCode)
            ->where('is_active', true)
            ->first();

        if ($method === null) {
            throw new SivarcartCoreException(sprintf(
                'El método de pago "%s" no está disponible.',
                $data->paymentMethodCode,
            ));
        }

        return $method;
    }

    /**
     * The sum of the lines before any discount.
     *
     * @param  list<PriceLineInput>  $lines
     */
    private function subtotal(array $lines): float
    {
        $subtotal = 0.0;

        foreach ($lines as $line) {
            $subtotal += $line['unit_price'] * $line['quantity'];
        }

        return round($subtotal, 2);
    }
}
