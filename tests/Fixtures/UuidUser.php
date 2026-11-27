<?php

declare(strict_types=1);

namespace Tarkontech\SivarCart\Tests\Fixtures;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * A user of the host application whose key is a UUID.
 *
 * The package derives the column of the staff from the model of the user, so
 * an application whose users have a UUID key does not need any change.
 */
class UuidUser extends Authenticatable
{
    use HasUuids;

    /**
     * @var string
     */
    protected $table = 'users';
}
