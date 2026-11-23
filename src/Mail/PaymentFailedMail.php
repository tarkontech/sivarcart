<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Mail;

use SivarcartCore\SivarcartCore\Enums\OrderNotification;
use SivarcartCore\SivarcartCore\Models\Order;

/**
 * The email the buyer receives when the payment of the order did not go
 * through.
 */
final class PaymentFailedMail extends OrderMail
{
    /**
     * What the gateway reported.
     */
    protected readonly string $reason;

    /**
     * @param  string  $reason  What the gateway reported.
     */
    public function __construct(
        Order $order,
        string $recipient,
        string $reason,
    ) {
        parent::__construct($order, $recipient);

        $this->reason = $reason;
    }

    /**
     * The notification this mail belongs to.
     */
    protected function notification(): OrderNotification
    {
        return OrderNotification::PAYMENT_FAILED;
    }

    /**
     * The markdown template this mail renders.
     */
    protected function template(): string
    {
        return 'sivarcart-core::mail.payment-failed';
    }

    /**
     * The data the template needs besides the order.
     *
     * @return array<string, mixed>
     */
    protected function templateData(): array
    {
        return [
            'reason' => $this->reason,
        ];
    }
}
