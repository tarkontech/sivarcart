<?php

declare(strict_types=1);

namespace Tarkontech\SivarCart\Exceptions;

use Tarkontech\SivarCart\Models\ProductVariant;

/**
 * The store does not have enough units to honour a request.
 */
class InsufficientStock extends SivarCartException
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
