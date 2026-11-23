<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Notifications;

use Illuminate\Contracts\Mail\Mailable;
use SivarcartCore\SivarcartCore\Mail\OrderShippedMail;

/**
 * Tells the buyer that the order moved.
 */
final class OrderShippedNotification extends BuyerNotification
{
    /**
     * The email the notification delivers.
     */
    public function toMail(object $notifiable): Mailable
    {
        return new OrderShippedMail($this->order, $this->recipient);
    }
}
