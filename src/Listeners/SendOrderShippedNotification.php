<?php

declare(strict_types=1);

namespace Tarkontech\SivarCart\Listeners;

use Illuminate\Notifications\Notification;
use Tarkontech\SivarCart\Enums\OrderNotification;
use Tarkontech\SivarCart\Events\OrderShipped;
use Tarkontech\SivarCart\Notifications\OrderShippedNotification;
use Tarkontech\SivarCart\Services\NotificationService;

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
