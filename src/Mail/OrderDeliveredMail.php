<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Mail;

use SivarcartCore\SivarcartCore\Enums\OrderNotification;

/**
 * The email the buyer receives when the order is delivered.
 */
final class OrderDeliveredMail extends OrderMail
{
    /**
     * The notification this mail belongs to.
     */
    protected function notification(): OrderNotification
    {
        return OrderNotification::ORDER_DELIVERED;
    }

    /**
     * The markdown template this mail renders.
     */
    protected function template(): string
    {
        return 'sivarcart-core::mail.order-delivered';
    }
}
