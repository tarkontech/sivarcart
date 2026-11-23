<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Notifications;

use Illuminate\Contracts\Mail\Mailable;
use SivarcartCore\SivarcartCore\Mail\OrderDeliveredMail;

/**
 * Tells the buyer that the order moved.
 */
final class OrderDeliveredNotification extends BuyerNotification
{
    /**
     * The email the notification delivers.
     */
    public function toMail(object $notifiable): Mailable
    {
        return new OrderDeliveredMail($this->order, $this->recipient);
    }
}
