<?php

declare(strict_types=1);

namespace Tarkontech\SivarCart\Services;

use Illuminate\Database\Eloquent\Collection;
use Tarkontech\SivarCart\Models\ShippingMethod;

/**
 * Reads the shipping methods of the store.
 *
 * The service only reads: the methods that a shopper can choose are the ones
 * that are active, and the order of the methods is the one the store
 * configured.
 */
final class ShippingMethodService
{
    /**
     * The shipping methods a shopper can choose.
     *
     * @return Collection<int, ShippingMethod>
     */
    public function available(): Collection
    {
        return ShippingMethod::query()
            ->where('is_active', true)
            ->orderBy('position')
            ->orderBy('id')
            ->get();
    }

    /**
     * The shipping method with that code, when it is available.
     */
    public function findByCode(string $code): ?ShippingMethod
    {
        return ShippingMethod::query()
            ->where('code', $code)
            ->where('is_active', true)
            ->first();
    }
}
