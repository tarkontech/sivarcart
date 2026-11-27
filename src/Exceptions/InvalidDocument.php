<?php

declare(strict_types=1);

namespace Tarkontech\SivarCart\Exceptions;

use Tarkontech\SivarCart\Enums\CustomerDocumentType;

/**
 * The identity document of the buyer does not hold the format of its type.
 */
class InvalidDocument extends SivarCartException
{
    /**
     * The document does not hold the format of its type.
     *
     * @param  'invalid_format'|'invalid_check_digit'|'invalid_length'  $reason
     */
    public static function with(CustomerDocumentType $type, string $reason): self
    {
        return new self((string) __('sivarcart::messages.customer_document.'.$reason, [
            'type' => $type->label(),
        ]));
    }
}
