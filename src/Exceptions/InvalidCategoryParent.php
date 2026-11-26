<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Exceptions;

/**
 * The category cannot be filed under the parent that was asked for.
 */
class InvalidCategoryParent extends SivarcartCoreException
{
    /**
     * The parent is the category itself, or one of the categories under it.
     *
     * A category that is under itself is a loop, and the catalog would never
     * stop walking it.
     */
    public static function cycle(): self
    {
        return new self('Una categoría no puede estar dentro de sí misma.');
    }
}
