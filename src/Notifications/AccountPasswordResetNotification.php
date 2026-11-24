<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Notifications;

use Illuminate\Contracts\Mail\Mailable;
use SivarcartCore\SivarcartCore\Mail\AccountPasswordResetMail;
use SivarcartCore\SivarcartCore\Models\Customer;

/**
 * Sends the buyer the link that resets the password of the account.
 */
final class AccountPasswordResetNotification extends BuyerAccountNotification
{
    /**
     * The token that proves the request comes from the buyer.
     */
    public readonly string $token;

    /**
     * The buyer that asked for the reset and the token of the reset.
     */
    public function __construct(Customer $customer, string $token)
    {
        parent::__construct($customer);

        $this->token = $token;
    }

    /**
     * The email the notification delivers.
     */
    public function toMail(object $notifiable): Mailable
    {
        return new AccountPasswordResetMail($this->customer, $this->token);
    }
}
