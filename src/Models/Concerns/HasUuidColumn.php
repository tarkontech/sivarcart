<?php

declare(strict_types=1);

namespace Tarkontech\SivarCart\Models\Concerns;

use Illuminate\Database\Eloquent\Concerns\HasUuids;

/**
 * Fills the "uuid" column with a UUID while keeping the auto incrementing
 * primary key untouched.
 *
 * The HasUuids trait on its own replaces the primary key with a UUID, which
 * this package does not want: orders, payments and products are referenced by
 * their numeric id internally and only expose the uuid to the outside world.
 */
trait HasUuidColumn
{
    use HasUuids;

    /**
     * Get the columns that should receive a unique identifier.
     *
     * @return list<string>
     */
    public function uniqueIds(): array
    {
        return ['uuid'];
    }
}
