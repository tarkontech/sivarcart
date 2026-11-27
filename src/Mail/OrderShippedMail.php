<?php

declare(strict_types=1);

namespace Tarkontech\SivarCart\Mail;

use Tarkontech\SivarCart\Enums\OrderNotification;

/**
 * The email the buyer receives when the order is shipped.
 */
final class OrderShippedMail extends OrderMail
{
    /**
     * The notification this mail belongs to.
     */
    protected function notification(): OrderNotification
    {
        return OrderNotification::ORDER_SHIPPED;
    }

    /**
     * The markdown template this mail renders.
     */
    protected function template(): string
    {
        return 'sivarcart::mail.order-shipped';
    }
}
