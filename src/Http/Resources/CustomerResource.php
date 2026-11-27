<?php

declare(strict_types=1);

namespace Tarkontech\SivarCart\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Tarkontech\SivarCart\Models\Customer;

/**
 * The account of a buyer, as the store API shows it.
 *
 * The resource never shows the password of the buyer.
 */
final class CustomerResource extends JsonResource
{
    /**
     * The buyer as the API shows it.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Customer $customer */
        $customer = $this->resource;

        return [
            'id' => $customer->uuid,
            'first_name' => $customer->first_name,
            'last_name' => $customer->last_name,
            'name' => trim(((string) $customer->first_name).' '.((string) $customer->last_name)),
            'email' => $customer->email,
            'phone' => $customer->phone,
            'document_type' => $customer->document_type?->value,
            'document_number' => $customer->document_number,
            'email_verified_at' => $customer->email_verified_at?->toIso8601String(),
            'accepts_marketing' => (bool) $customer->accepts_marketing,
            'created_at' => $customer->created_at?->toIso8601String(),
        ];
    }
}
