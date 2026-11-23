<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Listeners;

use Illuminate\Notifications\Notification;
use SivarcartCore\SivarcartCore\Enums\OrderNotification;
use SivarcartCore\SivarcartCore\Events\OrderDelivered;
use SivarcartCore\SivarcartCore\Notifications\OrderDeliveredNotification;
use SivarcartCore\SivarcartCore\Services\NotificationService;

/**
 * Writes to the buyer when the order is delivered.
 */
final class SendOrderDeliveredNotification
{
    public function __construct(
        private readonly NotificationService $notifications,
    ) {
        //
    }

    /**
     * Tell the buyer what happened to the order.
     */
    public function handle(OrderDelivered $event): void
    {
        $this->notifications->send(
            OrderNotification::ORDER_DELIVERED,
            $event->order,
            fn (string $recipient): Notification => new OrderDeliveredNotification($event->order, $recipient),
        );
    }
}
