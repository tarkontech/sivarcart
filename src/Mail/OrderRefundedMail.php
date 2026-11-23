<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Mail;

use SivarcartCore\SivarcartCore\Enums\OrderNotification;
use SivarcartCore\SivarcartCore\Models\Order;

/**
 * The email the buyer receives when the order is refunded.
 */
final class OrderRefundedMail extends OrderMail
{
    /**
     * How much was refunded.
     */
    protected readonly float $amount;

    /**
     * @param  float  $amount  How much was refunded.
     */
    public function __construct(
        Order $order,
        string $recipient,
        float $amount,
    ) {
        parent::__construct($order, $recipient);

        $this->amount = $amount;
    }

    /**
     * The notification this mail belongs to.
     */
    protected function notification(): OrderNotification
    {
        return OrderNotification::ORDER_REFUNDED;
    }

    /**
     * The markdown template this mail renders.
     */
    protected function template(): string
    {
        return 'sivarcart-core::mail.order-refunded';
    }

    /**
     * The data the template needs besides the order.
     *
     * @return array<string, mixed>
     */
    protected function templateData(): array
    {
        return [
            'amount' => $this->money($this->amount),
        ];
    }
}
