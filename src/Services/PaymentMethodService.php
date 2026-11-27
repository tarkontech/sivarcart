<?php

declare(strict_types=1);

namespace Tarkontech\SivarCart\Services;

use Illuminate\Database\Eloquent\Collection;
use Tarkontech\SivarCart\Models\PaymentMethod;

/**
 * Reads the payment methods of the store.
 *
 * The service only reads: the methods that a shopper can pay with are the
 * ones that are active, and the order of the methods is the one the store
 * configured.
 */
final class PaymentMethodService
{
    /**
     * The payment methods a shopper can pay with.
     *
     * @return Collection<int, PaymentMethod>
     */
    public function available(): Collection
    {
        return PaymentMethod::query()
            ->where('is_active', true)
            ->orderBy('position')
            ->orderBy('id')
            ->get();
    }

    /**
     * The payment method with that code, when it is available.
     */
    public function findByCode(string $code): ?PaymentMethod
    {
        return PaymentMethod::query()
            ->where('code', $code)
            ->where('is_active', true)
            ->first();
    }
}
