<?php

declare(strict_types=1);

namespace Tarkontech\SivarCart\Notifications;

use Illuminate\Contracts\Mail\Mailable;
use Tarkontech\SivarCart\Mail\OrderRefundedMail;
use Tarkontech\SivarCart\Models\Order;

/**
 * Tells the buyer that the order was refunded.
 */
final class OrderRefundedNotification extends BuyerNotification
{
    /**
     * How much was refunded.
     */
    public readonly float $amount;

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
     * The email the notification delivers.
     */
    public function toMail(object $notifiable): Mailable
    {
        return new OrderRefundedMail($this->order, $this->recipient, $this->amount);
    }
}
