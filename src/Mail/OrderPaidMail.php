<?php

declare(strict_types=1);

namespace Tarkontech\SivarCart\Mail;

use Tarkontech\SivarCart\Enums\OrderNotification;

/**
 * The email the buyer receives when the order is paid.
 */
final class OrderPaidMail extends OrderMail
{
    /**
     * The notification this mail belongs to.
     */
    protected function notification(): OrderNotification
    {
        return OrderNotification::ORDER_PAID;
    }

    /**
     * The markdown template this mail renders.
     */
    protected function template(): string
    {
        return 'sivarcart::mail.order-paid';
    }

    /**
     * The data the template needs besides the order.
     *
     * @return array<string, mixed>
     */
    protected function templateData(): array
    {
        return [
            'amount' => $this->money((float) $this->order->total),
        ];
    }
}
