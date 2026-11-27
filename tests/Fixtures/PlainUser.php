<?php

declare(strict_types=1);

namespace Tarkontech\SivarCart\Tests\Fixtures;

use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * A user of the host application that does not carry the trait of the roles
 * of the store.
 *
 * The trait is optional, so the package has to answer the question of the
 * permissions even when the model of the application does not have it.
 */
class PlainUser extends Authenticatable
{
    /**
     * @var string
     */
    protected $table = 'users';
}
