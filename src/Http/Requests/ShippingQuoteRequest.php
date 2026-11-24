<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * The shipping a shopper asks to be quoted.
 */
final class ShippingQuoteRequest extends FormRequest
{
    /**
     * The request only reaches a buyer that is signed in.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * The method and the address the shipping is quoted for.
     *
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'shipping_method_code' => ['required', 'string', 'max:64'],
            'address' => ['required', 'array'],
            'address.address_line1' => ['required', 'string', 'max:255'],
            'address.municipality' => ['required', 'string', 'max:120'],
            'address.department' => ['sometimes', 'nullable', 'string', 'max:100'],
            'address.department_id' => ['sometimes', 'nullable', 'integer', 'min:1'],
        ];
    }
}
