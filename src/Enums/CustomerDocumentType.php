<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Enums;

/**
 * The identity document a customer can present.
 */
enum CustomerDocumentType: string
{
    case NIT = 'nit';
    case RUC = 'ruc';
    case DUI = 'dui';
    case PASAPORTE = 'pasaporte';
    case OTRO = 'otro';

    /**
     * A human readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::NIT => 'NIT',
            self::RUC => 'RUC',
            self::DUI => 'DUI',
            self::PASAPORTE => 'Pasaporte',
            self::OTRO => 'Otro',
        };
    }
}
