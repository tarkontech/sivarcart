<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Http\Controllers\Admin;

use Illuminate\View\View;
use SivarcartCore\SivarcartCore\Models\Order;

/**
 * Shows one order of the store.
 *
 * The order is found by its uuid, the same way the API of the store finds it,
 * so the panel and the API talk about the same orders.
 */
final class OrderController
{
    /**
     * Show the order.
     */
    public function show(string $order): View
    {
        $model = Order::query()
            ->with(['items', 'payments', 'statusHistories'])
            ->where('uuid', $order)
            ->firstOrFail();

        return view('sivarcart-core::admin.order', [
            'order' => $model->uuid,
        ]);
    }
}
