<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * The email of the buyer that asks for the link that resets the password.
 */
final class SendResetLinkRequest extends FormRequest
{
    /**
     * The request is public: anybody can ask for the link.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * The email of the buyer.
     *
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email', 'max:255'],
        ];
    }
}
