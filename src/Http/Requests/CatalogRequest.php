<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * The filters the shopper reads the catalog with.
 */
final class CatalogRequest extends FormRequest
{
    /**
     * The request is public: anybody can read the catalog of the store.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * The filters the catalog answers with.
     *
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'category_id' => ['sometimes', 'integer', 'min:1'],
            'brand_id' => ['sometimes', 'integer', 'min:1'],
            'min_price' => ['sometimes', 'numeric', 'min:0'],
            'max_price' => ['sometimes', 'numeric', 'min:0'],
            'in_stock' => ['sometimes', 'boolean'],
            'search' => ['sometimes', 'string', 'max:120'],
            'sort' => ['sometimes', 'string', 'in:recent,price_asc,price_desc,name'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ];
    }
}
