<?php

declare(strict_types=1);

namespace Tarkontech\SivarCart\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * The variant a shopper puts in the cart of the store.
 *
 * The store names the variant with its uuid, the same way it names the
 * products and the categories: the buyer never sees an id of the database.
 */
final class StorefrontCartItemRequest extends FormRequest
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
            'variant_id' => ['required', 'string', 'max:64'],
            'quantity' => ['sometimes', 'integer', 'min:1', 'max:9999'],
        ];
    }
}
