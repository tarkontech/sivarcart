<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Listeners;

use Illuminate\Notifications\Notification;
use SivarcartCore\SivarcartCore\Enums\OrderNotification;
use SivarcartCore\SivarcartCore\Events\OrderPlaced;
use SivarcartCore\SivarcartCore\Notifications\OrderPlacedNotification;
use SivarcartCore\SivarcartCore\Services\NotificationService;

/**
 * Writes to the buyer when the order is placed.
 */
final class SendOrderPlacedNotification
{
    public function __construct(
        private readonly NotificationService $notifications,
    ) {
        //
    }

    /**
     * Tell the buyer what happened to the order.
     */
    public function handle(OrderPlaced $event): void
    {
        $this->notifications->send(
            OrderNotification::ORDER_PLACED,
            $event->order,
            fn (string $recipient): Notification => new OrderPlacedNotification($event->order, $recipient),
        );
    }
}
