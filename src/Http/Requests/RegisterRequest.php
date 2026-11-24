<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use SivarcartCore\SivarcartCore\Enums\CustomerDocumentType;

/**
 * The signup of a buyer.
 */
final class RegisterRequest extends FormRequest
{
    /**
     * The request is public: it is the one that creates the account.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * The attributes of the account.
     *
     * The document is not validated here: the domain validates it against the
     * type that declares it.
     *
     * @return array<string, list<string|Enum>>
     */
    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:150'],
            'last_name' => ['sometimes', 'nullable', 'string', 'max:150'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:30'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'document_type' => ['sometimes', 'nullable', 'string', Rule::enum(CustomerDocumentType::class)],
            'document_number' => ['sometimes', 'nullable', 'string', 'max:32'],
            'accepts_marketing' => ['sometimes', 'boolean'],
        ];
    }
}
