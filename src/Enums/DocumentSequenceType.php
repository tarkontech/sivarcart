<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Enums;

/**
 * The kind of document a sequence counter tracks.
 */
enum DocumentSequenceType: string
{
    case ORDER = 'order';
    case PAYMENT = 'payment';

    /**
     * A human readable label.
     *
     * The label comes from the language file of the package and not from the
     * code, so it speaks the language of the application.
     */
    public function label(): string
    {
        return (string) __('sivarcart-core::messages.document_sequence_type.'.$this->value);
    }
}
