<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Notifications;

use Illuminate\Contracts\Mail\Mailable;
use SivarcartCore\SivarcartCore\Mail\OrderCancelledMail;

/**
 * Tells the buyer that the order moved.
 */
final class OrderCancelledNotification extends BuyerNotification
{
    /**
     * The email the notification delivers.
     */
    public function toMail(object $notifiable): Mailable
    {
        return new OrderCancelledMail($this->order, $this->recipient);
    }
}
