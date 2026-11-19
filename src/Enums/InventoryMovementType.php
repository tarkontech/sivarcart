<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Enums;

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
     */
    public function label(): string
    {
        return match ($this) {
            self::SALE => 'Venta',
            self::RESTOCK => 'Reposición',
            self::ADJUSTMENT => 'Ajuste',
            self::RETURN => 'Devolución',
            self::RESERVATION => 'Reserva',
            self::RELEASE => 'Liberación',
        };
    }
}
