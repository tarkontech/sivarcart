<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Enums;

/**
 * The axis of an order that a recorded change belongs to.
 *
 * The history of an order keeps two axes apart: the lifecycle of the order
 * and the state of its payment. A row of the history belongs to one of them.
 */
enum OrderHistoryAxis: string
{
    case STATUS = 'status';
    case PAYMENT = 'payment';

    /**
     * A human readable label.
     *
     * The label comes from the language file of the package and not from the
     * code, so it speaks the language of the application.
     */
    public function label(): string
    {
        return (string) __('sivarcart-core::messages.order_history_axis.'.$this->value);
    }
}
