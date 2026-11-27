<?php

declare(strict_types=1);

namespace Tarkontech\SivarCart\Listeners;

use Illuminate\Notifications\Notification;
use Tarkontech\SivarCart\Enums\OrderNotification;
use Tarkontech\SivarCart\Events\OrderRefunded;
use Tarkontech\SivarCart\Notifications\OrderRefundedNotification;
use Tarkontech\SivarCart\Services\NotificationService;

/**
 * Writes to the buyer when the order is refunded.
 */
final class SendOrderRefundedNotification
{
    public function __construct(
        private readonly NotificationService $notifications,
    ) {
        //
    }

    /**
     * Tell the buyer that the money is on its way back.
     */
    public function handle(OrderRefunded $event): void
    {
        $this->notifications->send(
            OrderNotification::ORDER_REFUNDED,
            $event->order,
            fn (string $recipient): Notification => new OrderRefundedNotification(
                $event->order,
                $recipient,
                $event->amount,
            ),
        );
    }
}
