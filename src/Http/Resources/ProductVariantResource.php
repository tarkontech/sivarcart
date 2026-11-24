<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use SivarcartCore\SivarcartCore\Models\ProductOptionValue;
use SivarcartCore\SivarcartCore\Models\ProductVariant;

/**
 * A variant of a product, as the store API shows it.
 *
 * The variant is the unit that is sold, so it carries the price, the stock
 * and the sku.
 */
final class ProductVariantResource extends JsonResource
{
    /**
     * The variant as the API shows it.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var ProductVariant $variant */
        $variant = $this->resource;

        return [
            'id' => $variant->uuid,
            'sku' => $variant->sku,
            'name' => $variant->name,
            'price' => (float) $variant->price,
            'compare_at_price' => $variant->compare_at_price === null ? null : (float) $variant->compare_at_price,
            'stock_quantity' => (int) $variant->stock_quantity,
            'reserved_quantity' => (int) $variant->reserved_quantity,
            'available_stock' => $variant->availableStock(),
            'is_default' => (bool) $variant->is_default,
            'is_active' => (bool) $variant->is_active,
            'position' => (int) $variant->position,
            'image_path' => $variant->image_path,
            'barcode' => $variant->barcode,
            'weight' => $variant->weight === null ? null : (float) $variant->weight,
            'options' => $this->whenLoaded('optionValues', fn (): array => $variant->optionValues
                ->map(fn (ProductOptionValue $value): array => [
                    'id' => $value->getKey(),
                    'value' => $value->value,
                    'label' => $value->label,
                ])
                ->all()),
        ];
    }
}
