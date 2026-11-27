<?php

declare(strict_types=1);

namespace Tarkontech\SivarCart\Listeners;

use Illuminate\Notifications\Notification;
use Tarkontech\SivarCart\Enums\OrderNotification;
use Tarkontech\SivarCart\Events\OrderCancelled;
use Tarkontech\SivarCart\Notifications\OrderCancelledNotification;
use Tarkontech\SivarCart\Services\NotificationService;

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
