<?php

declare(strict_types=1);

namespace Tarkontech\SivarCart\Listeners;

use Illuminate\Notifications\Notification;
use Tarkontech\SivarCart\Enums\OrderNotification;
use Tarkontech\SivarCart\Events\OrderPlaced;
use Tarkontech\SivarCart\Notifications\OrderPlacedNotification;
use Tarkontech\SivarCart\Services\NotificationService;

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
