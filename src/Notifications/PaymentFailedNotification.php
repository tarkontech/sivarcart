<?php

declare(strict_types=1);

namespace Tarkontech\SivarCart\Notifications;

use Illuminate\Contracts\Mail\Mailable;
use Tarkontech\SivarCart\Mail\PaymentFailedMail;
use Tarkontech\SivarCart\Models\Order;

/**
 * Tells the buyer that the payment of the order did not go through.
 */
final class PaymentFailedNotification extends BuyerNotification
{
    /**
     * What the gateway reported.
     */
    public readonly string $reason;

    /**
     * @param  string  $reason  What the gateway reported.
     */
    public function __construct(
        Order $order,
        string $recipient,
        string $reason,
    ) {
        parent::__construct($order, $recipient);

        $this->reason = $reason;
    }

    /**
     * The email the notification delivers.
     */
    public function toMail(object $notifiable): Mailable
    {
        return new PaymentFailedMail($this->order, $this->recipient, $this->reason);
    }
}
