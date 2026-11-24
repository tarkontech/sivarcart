<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use SivarcartCore\SivarcartCore\Models\CustomerAddress;

/**
 * A saved address of a buyer, as the store API shows it.
 */
final class CustomerAddressResource extends JsonResource
{
    /**
     * The address as the API shows it.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var CustomerAddress $address */
        $address = $this->resource;

        return [
            'id' => $address->getKey(),
            'label' => $address->label,
            'first_name' => $address->first_name,
            'last_name' => $address->last_name,
            'phone' => $address->phone,
            'address_line1' => $address->address_line1,
            'address_line2' => $address->address_line2,
            'department' => $address->department?->name,
            'municipality' => $address->municipality,
            'city' => $address->city,
            'reference' => $address->reference,
            'postal_code' => $address->postal_code,
            'is_default' => (bool) $address->is_default,
            'is_billing' => (bool) $address->is_billing,
        ];
    }
}
