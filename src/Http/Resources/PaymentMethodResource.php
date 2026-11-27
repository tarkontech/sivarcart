<?php

declare(strict_types=1);

namespace Tarkontech\SivarCart\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Tarkontech\SivarCart\Models\PaymentMethod;

/**
 * A way of paying, as the store API shows it.
 *
 * The instructions and the icon are the ones the store configured: they tell
 * the buyer how to pay.
 */
final class PaymentMethodResource extends JsonResource
{
    /**
     * The method as the API shows it.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var PaymentMethod $method */
        $method = $this->resource;

        return [
            'id' => $method->uuid,
            'code' => $method->code,
            'name' => $method->name,
            'type' => $method->type->value,
            'instructions' => $method->instructions,
            'icon' => $method->icon,
        ];
    }
}
