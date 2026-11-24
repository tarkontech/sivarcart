<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use SivarcartCore\SivarcartCore\Models\Customer;

/**
 * The base of the notifications the account of a buyer receives.
 *
 * The notifications are queued: the email never holds the checkout nor the
 * signup, and a mailer that is down never breaks an account that is already
 * saved.
 */
abstract class BuyerAccountNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Customer $customer,
    ) {
        //
    }

    /**
     * The channels the notification goes through.
     *
     * A buyer without an email address has nowhere to receive the mail, so
     * the notification goes through no channel at all.
     *
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        if ($this->customer->email === null || $this->customer->email === '') {
            return [];
        }

        return ['mail'];
    }
}
