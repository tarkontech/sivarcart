<?php

declare(strict_types=1);

namespace Tarkontech\SivarCart\Enums;

/**
 * The reason a stock movement was recorded.
 */
enum InventoryMovementType: string
{
    case SALE = 'sale';
    case RESTOCK = 'restock';
    case ADJUSTMENT = 'adjustment';
    case RETURN = 'return';
    case RESERVATION = 'reservation';
    case RELEASE = 'release';

    /**
     * A human readable label.
     *
     * The label comes from the language file of the package and not from the
     * code, so it speaks the language of the application.
     */
    public function label(): string
    {
        return (string) __('sivarcart::messages.inventory_movement_type.'.$this->value);
    }
}
