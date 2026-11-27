<?php

declare(strict_types=1);

namespace Tarkontech\SivarCart\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Tarkontech\SivarCart\Models\Cart;

/**
 * The cart of a shopper, as the store API shows it.
 *
 * The totals of the cart are a cache of what the lines are worth, and the
 * shipping is not part of them: the shipping is quoted when the order is
 * placed.
 */
final class CartResource extends JsonResource
{
    /**
     * The cart as the API shows it.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Cart $cart */
        $cart = $this->resource;

        return [
            'id' => $cart->uuid,
            'currency' => $cart->currency,
            'item_count' => (int) $cart->item_count,
            'subtotal' => (float) $cart->subtotal,
            'discount_total' => (float) $cart->discount_total,
            'tax_total' => (float) $cart->tax_total,
            'shipping_total' => (float) $cart->shipping_total,
            'total' => (float) $cart->total,
            'coupon' => $cart->coupon?->code,
            'expires_at' => $cart->expires_at?->toIso8601String(),
            'items' => CartItemResource::collection($cart->items),
        ];
    }
}
