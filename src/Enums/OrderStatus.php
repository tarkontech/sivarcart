<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Enums;

/**
 * The lifecycle of an order.
 *
 * The allowed transitions live in the enum so that optional modules can reason
 * about the order lifecycle without reading the service layer.
 *
 * The order keeps the state of its payment in a separate field, so an order
 * that is delivered and not paid yet is a "delivered" order whose payment is
 * "unpaid". The two axes never say the same thing.
 */
enum OrderStatus: string
{
    case PENDING = 'pending';
    case PROCESSING = 'processing';
    case SHIPPED = 'shipped';
    case DELIVERED = 'delivered';
    case RETURNED = 'returned';
    case CANCELLED = 'cancelled';
    case REFUNDED = 'refunded';

    /**
     * The statuses an order may move to from the current one.
     *
     * @return list<self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::PENDING => [self::PROCESSING, self::SHIPPED, self::CANCELLED, self::REFUNDED],
            self::PROCESSING => [self::SHIPPED, self::CANCELLED, self::REFUNDED],
            self::SHIPPED => [self::DELIVERED, self::RETURNED, self::REFUNDED],
            self::DELIVERED => [self::RETURNED, self::REFUNDED],
            self::RETURNED => [self::REFUNDED],
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
     */
    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pendiente',
            self::PROCESSING => 'En preparación',
            self::SHIPPED => 'Enviada',
            self::DELIVERED => 'Entregada',
            self::RETURNED => 'Devuelta',
            self::CANCELLED => 'Cancelada',
            self::REFUNDED => 'Reembolsada',
        };
    }
}
