<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use SivarcartCore\SivarcartCore\Admin\AdminPresenter;
use SivarcartCore\SivarcartCore\Enums\OrderStatus;
use SivarcartCore\SivarcartCore\Models\Order;
use SivarcartCore\SivarcartCore\Services\Admin\OrderService;
use SivarcartCore\SivarcartCore\Support\Auth;

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
    public function show(Request $request, string $order): Response
    {
        $model = app(OrderService::class)->find($order);

        if (! $model instanceof Order) {
            abort(404, __('sivarcart-core::admin.orders.not_found'));
        }

        $canUpdate = Auth::hasStorePermission(Auth::user(), 'orders.update');

        return Inertia::render('Admin/Orders/Show', [
            'order' => AdminPresenter::orderDetail($model),
            'canUpdate' => $canUpdate,

            /*
            | The moves are the ones of the service of the orders. The staff
            | that cannot move an order does not receive them, so the panel
            | does not even offer them.
            */
            'transitions' => $canUpdate ? $this->transitions($model) : [],
        ]);
    }

    /**
     * The moves the staff of the store can do from the state the order is in.
     *
     * The refund is not one of them: it moves the axis of the payment and it
     * takes an amount, so it has a screen of its own.
     *
     * @return list<array{value: string, label: string, tone: string}>
     */
    private function transitions(Order $order): array
    {
        $allowed = [
            OrderStatus::PROCESSING,
            OrderStatus::SHIPPED,
            OrderStatus::DELIVERED,
            OrderStatus::CANCELLED,
        ];

        $transitions = [];

        foreach ($order->status->allowedTransitions() as $status) {
            if (! in_array($status, $allowed, true)) {
                continue;
            }

            $transitions[] = AdminPresenter::state($status->value, $status->label());
        }

        return $transitions;
    }
}
