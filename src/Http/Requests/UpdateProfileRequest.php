<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use SivarcartCore\SivarcartCore\Enums\CustomerDocumentType;

/**
 * The profile of a buyer.
 */
final class UpdateProfileRequest extends FormRequest
{
    /**
     * The request only reaches a buyer that is signed in.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * The attributes of the profile.
     *
     * The email is not part of the profile: changing it needs a new
     * verification, so it has a flow of its own.
     *
     * @return array<string, list<string|Enum>>
     */
    public function rules(): array
    {
        return [
            'first_name' => ['sometimes', 'string', 'max:150'],
            'last_name' => ['sometimes', 'nullable', 'string', 'max:150'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:30'],
            'document_type' => ['sometimes', 'nullable', 'string', Rule::enum(CustomerDocumentType::class)],
            'document_number' => ['sometimes', 'nullable', 'string', 'max:32'],
            'accepts_marketing' => ['sometimes', 'boolean'],
        ];
    }
}
