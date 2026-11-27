<?php

declare(strict_types=1);

namespace Tarkontech\SivarCart\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * The units of a line of the cart.
 */
final class UpdateCartItemRequest extends FormRequest
{
    /**
     * The request only reaches a buyer that is signed in.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * The units of the line.
     *
     * Zero units take the line out of the cart.
     *
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'quantity' => ['required', 'integer', 'min:0', 'max:9999'],
        ];
    }
}
