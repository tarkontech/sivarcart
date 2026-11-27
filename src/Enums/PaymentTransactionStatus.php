<?php

declare(strict_types=1);

namespace Tarkontech\SivarCart\Enums;

/**
 * The outcome of a single gateway transaction.
 */
enum PaymentTransactionStatus: string
{
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case FAILED = 'failed';

    /**
     * A human readable label.
     *
     * The label comes from the language file of the package and not from the
     * code, so it speaks the language of the application.
     */
    public function label(): string
    {
        return (string) __('sivarcart::messages.payment_transaction_status.'.$this->value);
    }
}
