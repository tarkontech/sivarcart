<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Enums;

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
     */
    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Borrador',
            self::PUBLISHED => 'Publicado',
            self::ARCHIVED => 'Archivado',
        };
    }
}
