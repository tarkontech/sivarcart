<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Enums;

/**
 * The way the catalog is sorted.
 *
 * The price of a product is the price of its variants, so the products sorted
 * by price are sorted by the cheapest variant they have.
 */
enum CatalogSort: string
{
    case RECENT = 'recent';
    case PRICE_ASC = 'price_asc';
    case PRICE_DESC = 'price_desc';
    case NAME = 'name';

    /**
     * A human readable label.
     *
     * The label comes from the language file of the package and not from the
     * code, so it speaks the language of the application.
     */
    public function label(): string
    {
        return (string) __('sivarcart-core::messages.catalog_sort.'.$this->value);
    }
}
