<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * The variant a shopper puts in the cart.
 */
final class StoreCartItemRequest extends FormRequest
{
    /**
     * The request only reaches a buyer that is signed in.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * The line of the cart.
     *
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'variant_id' => ['required', 'integer', 'min:1'],
            'quantity' => ['sometimes', 'integer', 'min:1', 'max:9999'],
        ];
    }
}
