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
     *
     * The label comes from the language file of the package and not from the
     * code, so it speaks the language of the application.
     */
    public function label(): string
    {
        return (string) __('sivarcart-core::messages.customer_document_type.'.$this->value);
    }
}
