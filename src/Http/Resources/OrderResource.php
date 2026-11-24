<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use SivarcartCore\SivarcartCore\Models\Order;
use SivarcartCore\SivarcartCore\Models\Payment;

/**
 * An order, as the store API shows it.
 *
 * The order has two axes: the state of the order and the state of its
 * payment. Both are shown, because an order can be delivered and unpaid at
 * the same time.
 */
final class OrderResource extends JsonResource
{
    /**
     * The order as the API shows it.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Order $order */
        $order = $this->resource;

        $payment = $order->payments->first();

        return [
            'id' => $order->uuid,
            'order_number' => $order->order_number,
            'status' => $order->status->value,
            'status_label' => $order->status->label(),
            'payment_status' => $order->payment_status->value,
            'payment_status_label' => $order->payment_status->label(),
            'currency' => $order->currency,
            'subtotal' => (float) $order->subtotal,
            'discount_total' => (float) $order->discount_total,
            'tax_total' => (float) $order->tax_total,
            'shipping_total' => (float) $order->shipping_total,
            'total' => (float) $order->total,
            'tax_inclusive' => (bool) $order->tax_inclusive,
            'coupon_code' => $order->coupon_code,
            'shipping_address' => $order->shipping_address,
            'billing_address' => $order->billing_address,
            'shipping_method_code' => $order->shipping_method_code,
            'shipping_method_name' => $order->shipping_method_name,
            'customer_name' => $order->customer_name,
            'customer_email' => $order->customer_email,
            'customer_phone' => $order->customer_phone,
            'customer_note' => $order->customer_note,
            'placed_at' => $order->placed_at?->toIso8601String(),
            'paid_at' => $order->paid_at?->toIso8601String(),
            'cancelled_at' => $order->cancelled_at?->toIso8601String(),
            'fulfilled_at' => $order->fulfilled_at?->toIso8601String(),
            'items' => OrderItemResource::collection($order->items),
            'payment' => $payment === null ? null : $this->payment($payment),
        ];
    }

    /**
     * The payment of the order, as the API shows it.
     *
     * @return array<string, mixed>
     */
    private function payment(Payment $payment): array
    {
        return [
            'status' => $payment->status->value,
            'method_code' => $payment->method_code,
            'gateway' => $payment->gateway,
            'reference' => $payment->reference,
            'amount' => (float) $payment->amount,
        ];
    }
}
