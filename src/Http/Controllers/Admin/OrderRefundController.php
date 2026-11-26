<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use SivarcartCore\SivarcartCore\Models\Order;
use SivarcartCore\SivarcartCore\Services\Admin\OrderService as AdminOrderService;
use SivarcartCore\SivarcartCore\Services\OrderService;
use SivarcartCore\SivarcartCore\Support\Auth;
use Throwable;

/**
 * Refunds an order of the store and registers that it came back.
 *
 * The two are not the same thing: a refund gives the money back and a return
 * says that the units came back to the store. The units go back to the stock
 * when the order is refunded, so a return on its own does not touch the
 * stock.
 */
final class OrderRefundController
{
    /**
     * Refund the order.
     */
    public function refund(Request $request, string $order): RedirectResponse
    {
        $model = app(AdminOrderService::class)->find($order);

        if (! $model instanceof Order) {
            abort(404, __('sivarcart-core::admin.orders.not_found'));
        }

        $attributes = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            app(OrderService::class)->refund(
                $model,
                (float) $attributes['amount'],
                $attributes['reason'] ?? null,
                Auth::user(),
            );
        } catch (Throwable $exception) {
            return back()
                ->withErrors(['amount' => $exception->getMessage()])
                ->setStatusCode(303);
        }

        Inertia::flash('notice', __('sivarcart-core::admin.orders.refunded', [
            'number' => (string) $model->order_number,
        ]));

        return back()->setStatusCode(303);
    }

    /**
     * Register that the order came back to the store.
     */
    public function returned(Request $request, string $order): RedirectResponse
    {
        $model = app(AdminOrderService::class)->find($order);

        if (! $model instanceof Order) {
            abort(404, __('sivarcart-core::admin.orders.not_found'));
        }

        $attributes = $request->validate([
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            app(OrderService::class)->markReturned(
                $model,
                Auth::user(),
                $attributes['reason'] ?? null,
            );
        } catch (Throwable $exception) {
            return back()
                ->withErrors(['reason' => $exception->getMessage()])
                ->setStatusCode(303);
        }

        Inertia::flash('notice', __('sivarcart-core::admin.orders.returned', [
            'number' => (string) $model->order_number,
        ]));

        return back()->setStatusCode(303);
    }
}
