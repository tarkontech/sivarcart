<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use SivarcartCore\SivarcartCore\Models\ShippingMethod;

/**
 * A way of delivering an order, as the store API shows it.
 *
 * The instructions are the ones the store configured: they tell the buyer
 * what to expect from the delivery.
 */
final class ShippingMethodResource extends JsonResource
{
    /**
     * The method as the API shows it.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var ShippingMethod $method */
        $method = $this->resource;

        return [
            'id' => $method->uuid,
            'code' => $method->code,
            'name' => $method->name,
            'type' => $method->type->value,
            'price' => (float) $method->price,
            'min_order_amount' => $method->min_order_amount === null ? null : (float) $method->min_order_amount,
            'max_order_amount' => $method->max_order_amount === null ? null : (float) $method->max_order_amount,
            'estimated_days' => $method->estimated_days === null ? null : (int) $method->estimated_days,
            'instructions' => $method->instructions,
        ];
    }
}
