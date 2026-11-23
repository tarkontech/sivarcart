<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Enums;

/**
 * Whether a payment method is collected through a gateway or paid offline.
 */
enum PaymentMethodType: string
{
    case ONLINE = 'online';
    case OFFLINE = 'offline';

    /**
     * A human readable label.
     *
     * The label comes from the language file of the package and not from the
     * code, so it speaks the language of the application.
     */
    public function label(): string
    {
        return (string) __('sivarcart-core::messages.payment_method_type.'.$this->value);
    }
}
