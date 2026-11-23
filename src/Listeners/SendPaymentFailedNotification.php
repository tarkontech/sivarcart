<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Listeners;

use Illuminate\Notifications\Notification;
use SivarcartCore\SivarcartCore\Enums\OrderNotification;
use SivarcartCore\SivarcartCore\Events\PaymentFailed;
use SivarcartCore\SivarcartCore\Notifications\PaymentFailedNotification;
use SivarcartCore\SivarcartCore\Services\NotificationService;

/**
 * Writes to the buyer when the payment of the order did not go through.
 */
final class SendPaymentFailedNotification
{
    public function __construct(
        private readonly NotificationService $notifications,
    ) {
        //
    }

    /**
     * Tell the buyer that the payment did not go through.
     */
    public function handle(PaymentFailed $event): void
    {
        $this->notifications->send(
            OrderNotification::PAYMENT_FAILED,
            $event->order,
            fn (string $recipient): Notification => new PaymentFailedNotification(
                $event->order,
                $recipient,
                $event->reason,
            ),
        );
    }
}
