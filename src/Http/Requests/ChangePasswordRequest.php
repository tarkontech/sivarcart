<?php

declare(strict_types=1);

namespace Tarkontech\SivarCart\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * The new password of a buyer.
 */
final class ChangePasswordRequest extends FormRequest
{
    /**
     * The request only reaches a buyer that is signed in.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * The passwords of the change.
     *
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }
}
