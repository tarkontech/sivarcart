<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Livewire\Admin\Customers;

use Illuminate\Contracts\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use SivarcartCore\SivarcartCore\Services\Admin\CustomerService;

/**
 * The buyers of the store.
 *
 * The staff of the store looks for a buyer by name, by email or by phone.
 */
class CustomerList extends Component
{
    use WithPagination;

    /**
     * What the staff is looking for.
     */
    #[Url(as: 'q', except: '')]
    public string $search = '';

    /**
     * Go back to the first page when the search changes.
     */
    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    /**
     * Render the list.
     */
    public function render(CustomerService $customers): View
    {
        return view('sivarcart-core::admin.pages.customers.index', [
            'customers' => $customers->paginate(
                search: $this->search,
                perPage: (int) config('sivarcart-core.admin.per_page', 25),
            ),
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
