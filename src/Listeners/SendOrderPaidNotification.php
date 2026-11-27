<?php

declare(strict_types=1);

namespace Tarkontech\SivarCart\Listeners;

use Illuminate\Notifications\Notification;
use Tarkontech\SivarCart\Enums\OrderNotification;
use Tarkontech\SivarCart\Events\OrderPaid;
use Tarkontech\SivarCart\Notifications\OrderPaidNotification;
use Tarkontech\SivarCart\Services\NotificationService;

/**
 * Writes to the buyer when the order is paid.
 */
final class SendOrderPaidNotification
{
    public function __construct(
        private readonly NotificationService $notifications,
    ) {
        //
    }

    /**
     * Tell the buyer what happened to the order.
     */
    public function handle(OrderPaid $event): void
    {
        $this->notifications->send(
            OrderNotification::ORDER_PAID,
            $event->order,
            fn (string $recipient): Notification => new OrderPaidNotification($event->order, $recipient),
        );
    }
}
