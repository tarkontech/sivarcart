<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Exceptions;

/**
 * The account cannot be created because the email is already taken.
 */
class CustomerAlreadyExists extends SivarcartCoreException
{
    /**
     * There is already a buyer with that email address.
     */
    public static function withEmail(string $email): self
    {
        return new self(sprintf('Ya existe una cuenta con el correo "%s".', $email));
    }
}
