<?php

declare(strict_types=1);

namespace Tarkontech\SivarCart\Listeners;

use Illuminate\Notifications\Notification;
use Tarkontech\SivarCart\Enums\OrderNotification;
use Tarkontech\SivarCart\Events\OrderDelivered;
use Tarkontech\SivarCart\Notifications\OrderDeliveredNotification;
use Tarkontech\SivarCart\Services\NotificationService;

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
