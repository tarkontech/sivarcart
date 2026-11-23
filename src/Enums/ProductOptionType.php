<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Enums;

/**
 * The kind of value a product option accepts.
 */
enum ProductOptionType: string
{
    case TEXT = 'text';
    case SELECT = 'select';
    case COLOR = 'color';

    /**
     * A human readable label.
     *
     * The label comes from the language file of the package and not from the
     * code, so it speaks the language of the application.
     */
    public function label(): string
    {
        return (string) __('sivarcart-core::messages.product_option_type.'.$this->value);
    }
}
