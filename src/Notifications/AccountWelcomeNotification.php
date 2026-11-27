<?php

declare(strict_types=1);

namespace Tarkontech\SivarCart\Notifications;

use Illuminate\Contracts\Mail\Mailable;
use Tarkontech\SivarCart\Mail\AccountWelcomeMail;

/**
 * Welcomes the buyer to the store.
 */
final class AccountWelcomeNotification extends BuyerAccountNotification
{
    /**
     * The email the notification delivers.
     */
    public function toMail(object $notifiable): Mailable
    {
        return new AccountWelcomeMail($this->customer);
    }
}
