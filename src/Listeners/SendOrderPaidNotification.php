<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Listeners;

use Illuminate\Notifications\Notification;
use SivarcartCore\SivarcartCore\Enums\OrderNotification;
use SivarcartCore\SivarcartCore\Events\OrderPaid;
use SivarcartCore\SivarcartCore\Notifications\OrderPaidNotification;
use SivarcartCore\SivarcartCore\Services\NotificationService;

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
