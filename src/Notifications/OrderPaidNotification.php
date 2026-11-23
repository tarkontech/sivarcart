<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Notifications;

use Illuminate\Contracts\Mail\Mailable;
use SivarcartCore\SivarcartCore\Mail\OrderPaidMail;

/**
 * Tells the buyer that the order moved.
 */
final class OrderPaidNotification extends BuyerNotification
{
    /**
     * The email the notification delivers.
     */
    public function toMail(object $notifiable): Mailable
    {
        return new OrderPaidMail($this->order, $this->recipient);
    }
}
