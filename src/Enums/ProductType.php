<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Enums;

/**
 * Whether a product has a single sellable unit or several variants.
 *
 * Every product has at least one variant. A simple product has exactly one
 * variant without option values, so pricing and stock always run through a
 * single code path.
 */
enum ProductType: string
{
    case SIMPLE = 'simple';
    case VARIABLE = 'variable';

    /**
     * A human readable label.
     *
     * The label comes from the language file of the package and not from the
     * code, so it speaks the language of the application.
     */
    public function label(): string
    {
        return (string) __('sivarcart-core::messages.product_type.'.$this->value);
    }
}
