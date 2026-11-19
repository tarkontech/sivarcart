<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Exceptions;

use SivarcartCore\SivarcartCore\Models\ProductVariant;

/**
 * The store does not have enough units to honour a request.
 */
class InsufficientStock extends SivarcartCoreException
{
    /**
     * The variant does not have enough units available.
     */
    public static function forVariant(ProductVariant $variant, int $requested): self
    {
        return new self(sprintf(
            'Solo hay %d unidades disponibles de "%s" y se pidieron %d.',
            $variant->availableStock(),
            $variant->name,
            $requested,
        ));
    }
}
