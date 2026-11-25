<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Exceptions;

/**
 * The file that was going to be stored is not a file the store accepts.
 */
class InvalidMediaFile extends SivarcartCoreException
{
    /**
     * The file is not of a kind the store accepts.
     */
    public static function withWrongType(): self
    {
        return new self((string) __('sivarcart-core::messages.media.invalid_type'));
    }

    /**
     * The file is bigger than the store accepts.
     */
    public static function tooBig(int $kilobytes): self
    {
        return new self((string) __('sivarcart-core::messages.media.too_big', [
            'size' => $kilobytes,
        ]));
    }
}
