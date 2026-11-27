<?php

declare(strict_types=1);

namespace Tarkontech\SivarCart\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Tarkontech\SivarCart\Models\OrderItem;

/**
 * An item of an order, as the store API shows it.
 *
 * The item is a snapshot: the name, the price and the tax rate are the ones
 * of the moment the order was placed.
 */
final class OrderItemResource extends JsonResource
{
    /**
     * The item as the API shows it.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var OrderItem $item */
        $item = $this->resource;

        return [
            'id' => $item->getKey(),
            'product_id' => $item->product_id,
            'product_variant_id' => $item->product_variant_id,
            'name' => $item->name,
            'sku' => $item->sku,
            'variant_options' => $item->variant_options ?? [],
            'unit_price' => (float) $item->unit_price,
            'quantity' => (int) $item->quantity,
            'line_subtotal' => (float) $item->line_subtotal,
            'discount_total' => (float) $item->discount_total,
            'tax_rate' => (float) $item->tax_rate,
            'tax_total' => (float) $item->tax_total,
            'line_total' => (float) $item->line_total,
        ];
    }
}
