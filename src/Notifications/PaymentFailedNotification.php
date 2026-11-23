<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Notifications;

use Illuminate\Contracts\Mail\Mailable;
use SivarcartCore\SivarcartCore\Mail\PaymentFailedMail;
use SivarcartCore\SivarcartCore\Models\Order;

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
