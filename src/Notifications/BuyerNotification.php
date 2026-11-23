<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use SivarcartCore\SivarcartCore\Models\Order;

/**
 * The base of the notifications the buyer receives.
 *
 * The notifications are queued: the email never holds the checkout, and a
 * mailer that is down never breaks an order that is already saved.
 */
abstract class BuyerNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Order $order,
        public readonly string $recipient,
    ) {
        //
    }

    /**
     * The channels the notification goes through.
     *
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }
}
