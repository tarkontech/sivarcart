<?php

declare(strict_types=1);

namespace Tarkontech\SivarCart;

use Tarkontech\SivarCart\Admin\AdminPanel;
use Tarkontech\SivarCart\Services\CartService;
use Tarkontech\SivarCart\Services\CatalogService;
use Tarkontech\SivarCart\Services\CustomerService;
use Tarkontech\SivarCart\Services\InventoryService;
use Tarkontech\SivarCart\Services\MediaService;
use Tarkontech\SivarCart\Services\OrderService;
use Tarkontech\SivarCart\Services\PaymentMethodService;
use Tarkontech\SivarCart\Services\PaymentService;
use Tarkontech\SivarCart\Services\ShippingMethodService;

/**
 * The entry point of the package.
 *
 * It only gathers the services of the domain, so a host application can
 * reach them with a facade. The services are injected in the constructor:
 * that is the way the package itself uses them.
 */
class SivarCart
{
    public function __construct(
        private readonly AdminPanel $admin,
        private readonly CartService $carts,
        private readonly CustomerService $customers,
        private readonly OrderService $orders,
        private readonly PaymentService $payments,
        private readonly InventoryService $inventory,
        private readonly CatalogService $catalog,
        private readonly PaymentMethodService $paymentMethods,
        private readonly ShippingMethodService $shippingMethods,
        private readonly MediaService $media,
    ) {
        //
    }

    /**
     * The registry of the administration panel.
     *
     * The host application registers its own pages and widgets here:
     *
     *     SivarCart::admin()->page(
     *         key: 'quick-view',
     *         label: 'Vista rápida',
     *         permission: 'orders.view',
     *         component: QuickView::class,
     *     );
     */
    public function admin(): AdminPanel
    {
        return $this->admin;
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

    /**
     * The service that reads the payment methods of the store.
     */
    public function paymentMethods(): PaymentMethodService
    {
        return $this->paymentMethods;
    }

    /**
     * The service that reads the shipping methods of the store.
     */
    public function shippingMethods(): ShippingMethodService
    {
        return $this->shippingMethods;
    }

    /**
     * The service that keeps the images of the store.
     */
    public function media(): MediaService
    {
        return $this->media;
    }
}
