<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Notifications;

use Illuminate\Contracts\Mail\Mailable;
use SivarcartCore\SivarcartCore\Mail\AccountVerifyEmailMail;

/**
 * Asks the buyer to verify the email address of the account.
 */
final class AccountEmailVerificationNotification extends BuyerAccountNotification
{
    /**
     * The email the notification delivers.
     */
    public function toMail(object $notifiable): Mailable
    {
        return new AccountVerifyEmailMail($this->customer);
    }
}
