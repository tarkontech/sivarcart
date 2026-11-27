<?php

declare(strict_types=1);

namespace Tarkontech\SivarCart\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Tarkontech\SivarCart\Admin\AdminPresenter;
use Tarkontech\SivarCart\Enums\OrderStatus;
use Tarkontech\SivarCart\Models\Order;
use Tarkontech\SivarCart\Services\Admin\OrderService;
use Tarkontech\SivarCart\Support\Auth;

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
            abort(404, __('sivarcart::admin.orders.not_found'));
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

            /*
            | A refund is not a move: it gives the money back, so it takes an
            | amount, and the panel is told how much of the payment is left.
            */
            'refundable' => $this->refundable($model),
            'canRefund' => $canUpdate && $this->refundable($model) > 0.0,
            'canReturn' => $canUpdate && $model->status->canTransitionTo(OrderStatus::RETURNED),
        ]);
    }

    /**
     * How much of the payment of the order can still be refunded.
     */
    private function refundable(Order $order): float
    {
        $payment = $order->payments->first();

        if ($payment === null) {
            return 0.0;
        }

        return round((float) $payment->amount - (float) $payment->refunded_amount, 2);
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
