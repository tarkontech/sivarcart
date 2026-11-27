<?php

declare(strict_types=1);

namespace Tarkontech\SivarCart\Enums;

/**
 * The publication state of a product.
 */
enum ProductStatus: string
{
    case DRAFT = 'draft';
    case PUBLISHED = 'published';
    case ARCHIVED = 'archived';

    /**
     * Whether the product is visible to shoppers.
     */
    public function isVisible(): bool
    {
        return $this === self::PUBLISHED;
    }

    /**
     * A human readable label.
     *
     * The label comes from the language file of the package and not from the
     * code, so it speaks the language of the application.
     */
    public function label(): string
    {
        return (string) __('sivarcart::messages.product_status.'.$this->value);
    }
}
