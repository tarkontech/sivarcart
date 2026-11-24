<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Notifications;

use Illuminate\Contracts\Mail\Mailable;
use SivarcartCore\SivarcartCore\Mail\AccountWelcomeMail;

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
