<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model as EloquentModel;

/**
 * Base class for every SivarCart Core model.
 *
 * Models declare their table explicitly because every table in this package is
 * prefixed with "sivarcart_", and they override {@see newFactory()} to point at
 * the factory shipped with the package.
 */
abstract class Model extends EloquentModel
{
    /** @use HasFactory<Factory> */
    use HasFactory;
}
