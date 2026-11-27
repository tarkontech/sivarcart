<?php

declare(strict_types=1);

namespace Tarkontech\SivarCart\Services;

use Illuminate\Database\Eloquent\Collection;
use Tarkontech\SivarCart\Models\Department;

/**
 * Reads the departments of the country of the store.
 *
 * The service only reads: the departments are the ones that are active, and
 * the order of them is the one the store configured.
 */
final class DepartmentService
{
    /**
     * The departments a shopper can deliver an order to.
     *
     * @return Collection<int, Department>
     */
    public function available(): Collection
    {
        return Department::query()
            ->where('is_active', true)
            ->orderBy('position')
            ->orderBy('name')
            ->get();
    }
}
