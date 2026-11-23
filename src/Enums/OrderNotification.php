<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Enums;

/**
 * The notifications the package sends to the buyer.
 *
 * The enum is the register of the notifications that exist: the panel of the
 * host application lists its cases, and the table of the notification
 * settings holds one row per notification the merchant has turned on or off.
 */
enum OrderNotification: string
{
    case ORDER_PLACED = 'order_placed';
    case ORDER_PAID = 'order_paid';
    case ORDER_SHIPPED = 'order_shipped';
    case ORDER_DELIVERED = 'order_delivered';
    case ORDER_CANCELLED = 'order_cancelled';
    case ORDER_REFUNDED = 'order_refunded';
    case PAYMENT_FAILED = 'payment_failed';

    /**
     * The label the panel of the merchant shows.
     *
     * The label comes from the language file and not from the code, so the
     * panel speaks the language of the application.
     */
    public function label(): string
    {
        return (string) __('sivarcart-core::messages.order_notifications.'.$this->value.'.label');
    }
}
