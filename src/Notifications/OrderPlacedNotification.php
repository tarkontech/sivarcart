<?php

declare(strict_types=1);

namespace Tarkontech\SivarCart\Notifications;

use Illuminate\Contracts\Mail\Mailable;
use Tarkontech\SivarCart\Mail\OrderPlacedMail;

/**
 * Tells the buyer that the order moved.
 */
final class OrderPlacedNotification extends BuyerNotification
{
    /**
     * The email the notification delivers.
     */
    public function toMail(object $notifiable): Mailable
    {
        return new OrderPlacedMail($this->order, $this->recipient);
    }
}
