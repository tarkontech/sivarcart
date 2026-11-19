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
     */
    public function label(): string
    {
        return match ($this) {
            self::TEXT => 'Texto',
            self::SELECT => 'Selección',
            self::COLOR => 'Color',
        };
    }
}
