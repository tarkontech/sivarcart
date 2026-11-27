<?php

declare(strict_types=1);

namespace Tarkontech\SivarCart\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * The discount code a shopper types.
 */
final class ApplyCouponRequest extends FormRequest
{
    /**
     * The request only reaches a buyer that is signed in.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * The code of the coupon.
     *
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:64'],
        ];
    }
}
