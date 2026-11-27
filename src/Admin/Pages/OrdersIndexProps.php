<?php

declare(strict_types=1);

namespace Tarkontech\SivarCart\Admin\Pages;

use Illuminate\Http\Request;
use Tarkontech\SivarCart\Admin\AdminPage;
use Tarkontech\SivarCart\Admin\AdminPageProps;
use Tarkontech\SivarCart\Admin\AdminPresenter;
use Tarkontech\SivarCart\Enums\OrderStatus;
use Tarkontech\SivarCart\Models\Order;
use Tarkontech\SivarCart\Services\Admin\OrderService;

/**
 * The data of the list of the orders of the panel.
 *
 * The staff of the store searches the orders by number or by buyer and
 * filters them by the state of their lifecycle. Both travel in the address of
 * the page, so the list can be shared and reloaded.
 */
final readonly class OrdersIndexProps implements AdminPageProps
{
    /**
     * @return array<string, mixed>
     */
    public function props(Request $request, AdminPage $page): array
    {
        $search = (string) $request->query('q', '');
        $status = (string) $request->query('estado', '');

        $orders = app(OrderService::class)->paginate(
            search: $search,
            status: $status,
            perPage: (int) config('sivarcart.admin.per_page', 25),
        );

        $statuses = [];

        foreach (OrderStatus::cases() as $option) {
            $statuses[] = AdminPresenter::state($option->value, $option->label());
        }

        return [
            'orders' => AdminPresenter::paginate(
                $orders,
                fn (Order $order): array => AdminPresenter::orderListItem($order),
            ),
            'statuses' => $statuses,
            'filters' => ['q' => $search, 'status' => $status],
        ];
    }
}
