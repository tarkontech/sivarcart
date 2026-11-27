<?php

declare(strict_types=1);

namespace Tarkontech\SivarCart\Services;

use Closure;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use Tarkontech\SivarCart\Enums\OrderNotification;
use Tarkontech\SivarCart\Models\NotificationSetting;
use Tarkontech\SivarCart\Models\Order;
use Throwable;

/**
 * Sends the notifications of the buyer.
 *
 * The service owns the whole policy of the notifications: whether one of them
 * leaves the store, who receives it and what happens when the mailer fails.
 * The listeners only say which notification belongs to which event.
 */
final class NotificationService
{
    /**
     * Whether the store sends the given notification.
     *
     * The table of the notification settings holds what the merchant changed:
     * a notification without a row follows the default of the configuration.
     */
    public function enabled(OrderNotification $notification): bool
    {
        if (! (bool) config('sivarcart.notifications.enabled', true)) {
            return false;
        }

        $setting = NotificationSetting::query()
            ->where('notification', $notification->value)
            ->first();

        if ($setting === null) {
            return true;
        }

        return $setting->is_enabled;
    }

    /**
     * The address the notification goes to.
     *
     * An order placed before the package asked for an account may not have an
     * address, and then there is nobody to write to.
     */
    public function recipient(Order $order): ?string
    {
        $email = $order->customer_email;

        if (! is_string($email)) {
            return null;
        }

        $email = trim($email);

        return $email === '' ? null : $email;
    }

    /**
     * Send a notification to the buyer of the order.
     *
     * The message is built with the address it goes to, so the mailable
     * behind it always knows who it writes to.
     *
     * A failing mailer never breaks the order: the error is written to the
     * log and the caller carries on.
     *
     * @param  Closure(string): Notification  $build  Builds the message for the address it receives.
     */
    public function send(OrderNotification $notification, Order $order, Closure $build): void
    {
        if (! $this->enabled($notification)) {
            return;
        }

        $recipient = $this->recipient($order);

        if ($recipient === null) {
            return;
        }

        try {
            NotificationFacade::route('mail', $recipient)->notify($build($recipient));
        } catch (Throwable $exception) {
            Log::error('SivarCart could not send the notification to the buyer of the order.', [
                'notification' => $notification->value,
                'order_number' => $order->order_number,
                'exception' => $exception->getMessage(),
            ]);
        }
    }
}
