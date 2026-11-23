<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Listeners;

use Illuminate\Notifications\Notification;
use SivarcartCore\SivarcartCore\Enums\OrderNotification;
use SivarcartCore\SivarcartCore\Events\OrderCancelled;
use SivarcartCore\SivarcartCore\Notifications\OrderCancelledNotification;
use SivarcartCore\SivarcartCore\Services\NotificationService;

/**
 * Writes to the buyer when the order is cancelled.
 */
final class SendOrderCancelledNotification
{
    public function __construct(
        private readonly NotificationService $notifications,
    ) {
        //
    }

    /**
     * Tell the buyer what happened to the order.
     */
    public function handle(OrderCancelled $event): void
    {
        $this->notifications->send(
            OrderNotification::ORDER_CANCELLED,
            $event->order,
            fn (string $recipient): Notification => new OrderCancelledNotification($event->order, $recipient),
        );
    }
}
