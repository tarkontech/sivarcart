<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Listeners;

use Illuminate\Notifications\Notification;
use SivarcartCore\SivarcartCore\Enums\OrderNotification;
use SivarcartCore\SivarcartCore\Events\OrderRefunded;
use SivarcartCore\SivarcartCore\Notifications\OrderRefundedNotification;
use SivarcartCore\SivarcartCore\Services\NotificationService;

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
