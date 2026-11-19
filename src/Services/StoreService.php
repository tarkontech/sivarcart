<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Services;

use SivarcartCore\SivarcartCore\Models\Store;

/**
 * The store this installation serves.
 *
 * There is a single store per installation, so its settings are read from the
 * one row of the table. When the store was not created yet the defaults of
 * the configuration file are used.
 */
final class StoreService
{
    /**
     * The store of the installation.
     */
    public function current(): ?Store
    {
        return Store::query()->first();
    }

    /**
     * The currency the prices are expressed in.
     */
    public function currency(): string
    {
        return $this->current()->currency ?? (string) config('sivarcart-core.store.currency', 'USD');
    }

    /**
     * Whether the prices of the catalog already include the tax.
     */
    public function pricesIncludeTax(): bool
    {
        $store = $this->current();

        if ($store !== null) {
            return $store->prices_include_tax;
        }

        return (bool) config('sivarcart-core.store.prices_include_tax', true);
    }

    /**
     * The tax rate that applies when a product does not name one.
     */
    public function defaultTaxRate(): float
    {
        $store = $this->current();

        if ($store !== null) {
            return (float) $store->default_tax_rate;
        }

        return (float) config('sivarcart-core.store.default_tax_rate', 0.13);
    }

    /**
     * The prefix of the order numbers.
     */
    public function orderNumberPrefix(): string
    {
        return $this->current()->order_number_prefix
            ?? (string) config('sivarcart-core.store.order_number_prefix', 'SIV');
    }

    /**
     * The prefix of the payment references.
     */
    public function paymentNumberPrefix(): string
    {
        return $this->current()->payment_number_prefix
            ?? (string) config('sivarcart-core.store.payment_number_prefix', 'PAY');
    }

    /**
     * The timezone the store operates in.
     */
    public function timezone(): string
    {
        $timezone = $this->current()?->timezone;

        if (is_string($timezone) && $timezone !== '') {
            return $timezone;
        }

        return (string) config('app.timezone', 'UTC');
    }
}
