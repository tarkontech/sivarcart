<?php

declare(strict_types=1);

namespace Tarkontech\SivarCart\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * The credentials of a buyer that signs in.
 */
final class LoginRequest extends FormRequest
{
    /**
     * The request is public: it is the one that starts the session.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * The credentials of the buyer.
     *
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string'],
        ];
    }
}
