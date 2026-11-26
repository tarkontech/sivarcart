<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use SivarcartCore\SivarcartCore\Enums\OrderStatus;
use SivarcartCore\SivarcartCore\Models\Order;
use SivarcartCore\SivarcartCore\Services\Admin\OrderService as AdminOrderService;
use SivarcartCore\SivarcartCore\Services\OrderService;
use SivarcartCore\SivarcartCore\Support\Auth;
use Throwable;

/**
 * Moves one order of the store through its lifecycle.
 *
 * The moves are the ones of the service of the orders, so the panel never
 * writes the state of an order by itself.
 */
final class OrderStatusController
{
    /**
     * Move the order to the state the staff asked for.
     */
    public function update(Request $request, string $order): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'string', 'max:32'],
        ]);

        $model = app(AdminOrderService::class)->find($order);

        if (! $model instanceof Order) {
            abort(404, __('sivarcart-core::admin.orders.not_found'));
        }

        $status = OrderStatus::tryFrom($validated['status']);

        if (! $status instanceof OrderStatus || ! $this->isMovable($status)) {
            return back()
                ->withErrors(['status' => __('sivarcart-core::admin.orders.unknown_status')])
                ->setStatusCode(303);
        }

        if (! Auth::hasStorePermission(Auth::user(), 'orders.update')) {
            abort(403, 'No tienes permiso para cambiar los pedidos.');
        }

        $orders = app(OrderService::class);

        try {
            match ($status) {
                OrderStatus::PROCESSING => $orders->prepare($model, Auth::user()),
                OrderStatus::SHIPPED => $orders->ship($model, Auth::user()),
                OrderStatus::DELIVERED => $orders->deliver($model, Auth::user()),
                OrderStatus::CANCELLED => $orders->cancel($model),
            };
        } catch (Throwable $exception) {
            return back()
                ->withErrors(['status' => $exception->getMessage()])
                ->setStatusCode(303);
        }

        Inertia::flash('notice', __('sivarcart-core::admin.orders.moved', [
            'status' => $model->status->label(),
        ]));

        return back()->setStatusCode(303);
    }

    /**
     * Whether the panel can move an order to that state.
     *
     * The refund is not one of them: it moves the axis of the payment and it
     * takes an amount, so it has a screen of its own.
     */
    private function isMovable(OrderStatus $status): bool
    {
        return in_array($status, [
            OrderStatus::PROCESSING,
            OrderStatus::SHIPPED,
            OrderStatus::DELIVERED,
            OrderStatus::CANCELLED,
        ], true);
    }
}
