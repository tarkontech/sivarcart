<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore;

use SivarcartCore\SivarcartCore\Services\CartService;
use SivarcartCore\SivarcartCore\Services\CatalogService;
use SivarcartCore\SivarcartCore\Services\CustomerService;
use SivarcartCore\SivarcartCore\Services\InventoryService;
use SivarcartCore\SivarcartCore\Services\OrderService;
use SivarcartCore\SivarcartCore\Services\PaymentService;

/**
 * The entry point of the package.
 *
 * It only gathers the services of the domain, so a host application can
 * reach them with a facade. The services are injected in the constructor:
 * that is the way the package itself uses them.
 */
class SivarcartCore
{
    public function __construct(
        private readonly CartService $carts,
        private readonly CustomerService $customers,
        private readonly OrderService $orders,
        private readonly PaymentService $payments,
        private readonly InventoryService $inventory,
        private readonly CatalogService $catalog,
    ) {
        //
    }

    /**
     * The service that keeps the carts.
     */
    public function carts(): CartService
    {
        return $this->carts;
    }

    /**
     * The service that keeps the account of the buyers.
     */
    public function customers(): CustomerService
    {
        return $this->customers;
    }

    /**
     * The service that keeps the orders.
     */
    public function orders(): OrderService
    {
        return $this->orders;
    }

    /**
     * The service that keeps the payments.
     */
    public function payments(): PaymentService
    {
        return $this->payments;
    }

    /**
     * The service that moves the stock.
     */
    public function inventory(): InventoryService
    {
        return $this->inventory;
    }

    /**
     * The service that reads the catalog.
     */
    public function catalog(): CatalogService
    {
        return $this->catalog;
    }
}
