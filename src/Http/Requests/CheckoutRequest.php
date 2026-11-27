<?php

declare(strict_types=1);

namespace Tarkontech\SivarCart\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Everything the shopper sends when the order is placed.
 */
final class CheckoutRequest extends FormRequest
{
    /**
     * The request only reaches a buyer that is signed in.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * The data of the order.
     *
     * The address carries the keys the domain knows: the department can be
     * named or given by its id.
     *
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'customer_name' => ['required', 'string', 'max:150'],
            'customer_email' => ['sometimes', 'nullable', 'string', 'email', 'max:255'],
            'customer_phone' => ['sometimes', 'nullable', 'string', 'max:30'],
            'shipping_address' => ['required', 'array'],
            'shipping_address.first_name' => ['sometimes', 'nullable', 'string', 'max:150'],
            'shipping_address.last_name' => ['sometimes', 'nullable', 'string', 'max:150'],
            'shipping_address.phone' => ['sometimes', 'nullable', 'string', 'max:30'],
            'shipping_address.address_line1' => ['required', 'string', 'max:255'],
            'shipping_address.address_line2' => ['sometimes', 'nullable', 'string', 'max:255'],
            'shipping_address.department' => ['sometimes', 'nullable', 'string', 'max:100'],
            'shipping_address.department_id' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'shipping_address.municipality' => ['required', 'string', 'max:120'],
            'shipping_address.city' => ['sometimes', 'nullable', 'string', 'max:120'],
            'shipping_address.reference' => ['sometimes', 'nullable', 'string', 'max:255'],
            'shipping_address.postal_code' => ['sometimes', 'nullable', 'string', 'max:20'],
            'billing_address' => ['sometimes', 'nullable', 'array'],
            'billing_address.address_line1' => ['required_with:billing_address', 'string', 'max:255'],
            'billing_address.municipality' => ['required_with:billing_address', 'string', 'max:120'],
            'shipping_method_code' => ['sometimes', 'nullable', 'string', 'max:64'],
            'payment_method_code' => ['sometimes', 'nullable', 'string', 'max:64'],
            'coupon_code' => ['sometimes', 'nullable', 'string', 'max:64'],
            'customer_note' => ['sometimes', 'nullable', 'string', 'max:1000'],
        ];
    }
}
