<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Listeners;

use Illuminate\Notifications\Notification;
use SivarcartCore\SivarcartCore\Enums\OrderNotification;
use SivarcartCore\SivarcartCore\Events\OrderShipped;
use SivarcartCore\SivarcartCore\Notifications\OrderShippedNotification;
use SivarcartCore\SivarcartCore\Services\NotificationService;

/**
 * Writes to the buyer when the order is shipped.
 */
final class SendOrderShippedNotification
{
    public function __construct(
        private readonly NotificationService $notifications,
    ) {
        //
    }

    /**
     * Tell the buyer what happened to the order.
     */
    public function handle(OrderShipped $event): void
    {
        $this->notifications->send(
            OrderNotification::ORDER_SHIPPED,
            $event->order,
            fn (string $recipient): Notification => new OrderShippedNotification($event->order, $recipient),
        );
    }
}
