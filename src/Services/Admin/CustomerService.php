<?php

declare(strict_types=1);

namespace Tarkontech\SivarCart\Services\Admin;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Tarkontech\SivarCart\Models\Customer;

/**
 * The buyers of the store, as the staff of the store sees them.
 *
 * The service of the account of the buyers is the one of the buyer himself:
 * it registers, it changes the profile and it signs in. This one is the list
 * the staff of the store reads.
 */
final class CustomerService
{
    /**
     * The buyers of the store.
     *
     * @return LengthAwarePaginator<int, Customer>
     */
    public function paginate(string $search = '', int $perPage = 25): LengthAwarePaginator
    {
        return Customer::query()
            ->withCount('orders')
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('email', 'like', '%'.$search.'%')
                        ->orWhere('first_name', 'like', '%'.$search.'%')
                        ->orWhere('last_name', 'like', '%'.$search.'%')
                        ->orWhere('phone', 'like', '%'.$search.'%');
                });
            })
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    /**
     * One buyer of the store, by its uuid.
     */
    public function find(string $uuid): ?Customer
    {
        return Customer::query()
            ->with(['addresses', 'orders'])
            ->where('uuid', $uuid)
            ->first();
    }
}
