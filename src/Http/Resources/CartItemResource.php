<?php

declare(strict_types=1);

namespace Tarkontech\SivarCart\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Tarkontech\SivarCart\Models\CartItem;

/**
 * A line of the cart of a shopper, as the store API shows it.
 */
final class CartItemResource extends JsonResource
{
    /**
     * The line as the API shows it.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var CartItem $item */
        $item = $this->resource;

        return [
            'id' => $item->getKey(),
            'product_id' => $item->product_id,
            'product_variant_id' => $item->product_variant_id,
            'name' => $item->name,
            'sku' => $item->sku,
            'options' => $item->options ?? [],
            'unit_price' => (float) $item->unit_price,
            'quantity' => (int) $item->quantity,
            'line_subtotal' => (float) $item->line_subtotal,
            'discount_total' => (float) $item->discount_total,
            'tax_total' => (float) $item->tax_total,
            'line_total' => (float) $item->line_total,
        ];
    }
}
