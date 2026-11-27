<?php

declare(strict_types=1);

namespace Tarkontech\SivarCart\Listeners;

use Illuminate\Notifications\Notification;
use Tarkontech\SivarCart\Enums\OrderNotification;
use Tarkontech\SivarCart\Events\PaymentFailed;
use Tarkontech\SivarCart\Notifications\PaymentFailedNotification;
use Tarkontech\SivarCart\Services\NotificationService;

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
