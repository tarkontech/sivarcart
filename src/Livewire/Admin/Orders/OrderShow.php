<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Livewire\Admin\Orders;

use Illuminate\Contracts\View\View;
use Livewire\Component;
use SivarcartCore\SivarcartCore\Enums\OrderStatus;
use SivarcartCore\SivarcartCore\Models\Order;
use SivarcartCore\SivarcartCore\Services\Admin\OrderService as AdminOrderService;
use SivarcartCore\SivarcartCore\Services\OrderService;
use SivarcartCore\SivarcartCore\Support\Auth;
use Throwable;

/**
 * One order of the store.
 *
 * The staff of the store reads the order and moves it through its lifecycle.
 * The moves are the ones of the service of the orders, so the panel never
 * writes the state of an order by itself.
 */
class OrderShow extends Component
{
    /**
     * The uuid of the order.
     */
    public string $orderUuid = '';

    /**
     * The message that tells the staff what happened.
     */
    public ?string $notice = null;

    /**
     * The message that tells the staff what went wrong.
     */
    public ?string $error = null;

    /**
     * Take the order from the route.
     */
    public function mount(string $order): void
    {
        $this->orderUuid = $order;
    }

    /**
     * Move the order to the state the staff asked for.
     */
    public function move(string $status): void
    {
        if (! Auth::hasStorePermission(Auth::user(), 'orders.update')) {
            abort(403, 'No tienes permiso para cambiar los pedidos.');
        }

        $model = $this->model();

        if (! $model instanceof Order) {
            return;
        }

        $orders = app(OrderService::class);

        try {
            match ($status) {
                'processing' => $orders->prepare($model, Auth::user()),
                'shipped' => $orders->ship($model, Auth::user()),
                'delivered' => $orders->deliver($model, Auth::user()),
                'cancelled' => $orders->cancel($model),
                default => null,
            };
        } catch (Throwable $exception) {
            $this->error = $exception->getMessage();

            return;
        }

        $this->notice = __('sivarcart-core::admin.orders.moved', [
            'status' => $model->status->label(),
        ]);

        $this->error = null;
    }

    /**
     * Render the order.
     */
    public function render(): View
    {
        $model = $this->model();

        return view('sivarcart-core::admin.pages.orders.show', [
            'order' => $model,
            'canUpdate' => Auth::hasStorePermission(Auth::user(), 'orders.update'),
            'transitions' => $model instanceof Order ? $this->transitions($model) : [],
        ]);
    }

    /**
     * The moves the staff of the store can do from the state the order is in.
     *
     * The refund is not one of them: it moves the axis of the payment and it
     * takes an amount, so it has a screen of its own.
     *
     * @return list<OrderStatus>
     */
    private function transitions(Order $order): array
    {
        $allowed = [OrderStatus::PROCESSING, OrderStatus::SHIPPED, OrderStatus::DELIVERED, OrderStatus::CANCELLED];

        return array_values(array_filter(
            $order->status->allowedTransitions(),
            fn (OrderStatus $status): bool => in_array($status, $allowed, true),
        ));
    }

    /**
     * The order of the route.
     */
    private function model(): ?Order
    {
        return app(AdminOrderService::class)->find($this->orderUuid);
    }
}
