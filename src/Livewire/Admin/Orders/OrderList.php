<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Livewire\Admin\Orders;

use Illuminate\Contracts\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use SivarcartCore\SivarcartCore\Enums\OrderStatus;
use SivarcartCore\SivarcartCore\Services\Admin\OrderService as AdminOrderService;

/**
 * The list of the orders of the store.
 *
 * The staff of the store searches the orders by number or by buyer and
 * filters them by the state of their lifecycle.
 */
class OrderList extends Component
{
    use WithPagination;

    /**
     * What the staff is looking for.
     */
    #[Url(as: 'q', except: '')]
    public string $search = '';

    /**
     * The state of the lifecycle the staff is filtering by.
     */
    #[Url(as: 'estado', except: '')]
    public string $status = '';

    /**
     * Go back to the first page when the search changes.
     */
    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    /**
     * Go back to the first page when the filter changes.
     */
    public function updatingStatus(): void
    {
        $this->resetPage();
    }

    /**
     * Render the list.
     */
    public function render(AdminOrderService $orders): View
    {
        return view('sivarcart-core::admin.pages.orders.index', [
            'orders' => $orders->paginate(
                search: $this->search,
                status: $this->status,
                perPage: (int) config('sivarcart-core.admin.per_page', 25),
            ),
            'statuses' => OrderStatus::cases(),
        ]);
    }

    /**
     * The view of the pagination of the panel.
     *
     * The one of Livewire is written for Tailwind, and the panel does not ask
     * the merchant to install Tailwind, so it brings its own.
     */
    public function paginationView(): string
    {
        return 'sivarcart-core::admin.pagination';
    }
}
