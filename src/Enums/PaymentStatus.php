<?php

declare(strict_types=1);

namespace Tarkontech\SivarCart\Enums;

/**
 * The payment state of an order.
 *
 * This is the order facing view of the payment. The payment record itself
 * keeps its own, more granular status in {@see PaymentRecordStatus}.
 */
enum PaymentStatus: string
{
    case UNPAID = 'unpaid';
    case PENDING = 'pending';
    case PAID = 'paid';
    case FAILED = 'failed';
    case CANCELLED = 'cancelled';
    case REFUNDED = 'refunded';
    case PARTIALLY_REFUNDED = 'partially_refunded';

    /**
     * Whether the order has been paid for.
     */
    public function isPaid(): bool
    {
        return in_array($this, [self::PAID, self::PARTIALLY_REFUNDED], true);
    }

    /**
     * The statuses the payment of an order may move to from the current one.
     *
     * The payment is an axis of its own: it does not follow the lifecycle of
     * the order. An order may be delivered and still be unpaid, and it may be
     * paid and still be pending to be prepared.
     *
     * @return list<self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::UNPAID => [self::PENDING, self::PAID, self::FAILED, self::CANCELLED, self::REFUNDED],
            self::PENDING => [self::PAID, self::FAILED, self::CANCELLED, self::REFUNDED],
            self::PAID => [self::PARTIALLY_REFUNDED, self::REFUNDED],
            self::PARTIALLY_REFUNDED => [self::REFUNDED],
            self::FAILED => [self::PENDING, self::PAID, self::CANCELLED, self::REFUNDED],
            self::CANCELLED => [],
            self::REFUNDED => [],
        };
    }

    /**
     * Whether the status cannot be left.
     */
    public function isTerminal(): bool
    {
        return $this->allowedTransitions() === [];
    }

    /**
     * Whether moving from this status to the given one is allowed.
     */
    public function canTransitionTo(self $status): bool
    {
        return in_array($status, $this->allowedTransitions(), true);
    }

    /**
     * A human readable label.
     *
     * The label comes from the language file of the package and not from the
     * code, so it speaks the language of the application.
     */
    public function label(): string
    {
        return (string) __('sivarcart::messages.payment_status.'.$this->value);
    }
}
