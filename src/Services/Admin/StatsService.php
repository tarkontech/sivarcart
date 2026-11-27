<?php

declare(strict_types=1);

namespace Tarkontech\SivarCart\Services\Admin;

use Tarkontech\SivarCart\Enums\OrderStatus;
use Tarkontech\SivarCart\Models\Order;
use Tarkontech\SivarCart\Models\Product;
use Tarkontech\SivarCart\Models\ProductVariant;

/**
 * The numbers the dashboard of the panel shows.
 *
 * The package does not have a layer of reports: the analytics a merchant needs
 * depend on the business. The dashboard of the panel needs a handful of
 * counters though, so they live here, and they are counters, not reports.
 */
final class StatsService
{
    /**
     * The orders placed today.
     */
    public function ordersToday(): int
    {
        return Order::query()
            ->whereDate('placed_at', today())
            ->count();
    }

    /**
     * The orders the staff still has to work on.
     */
    public function ordersAwaiting(): int
    {
        return Order::query()
            ->whereIn('status', [OrderStatus::PENDING->value, OrderStatus::PROCESSING->value])
            ->count();
    }

    /**
     * The orders that were paid but not delivered yet.
     */
    public function ordersUnpaid(): int
    {
        return Order::query()
            ->where('payment_status', 'unpaid')
            ->count();
    }

    /**
     * The products of the catalog.
     */
    public function products(): int
    {
        return Product::query()->count();
    }

    /**
     * The sellable units that are running out of stock.
     */
    public function lowStock(): int
    {
        return ProductVariant::query()
            ->where('is_active', true)
            ->whereColumn('stock_quantity', '<=', 'low_stock_threshold')
            ->count();
    }
}
