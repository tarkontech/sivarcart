<?php

declare(strict_types=1);

namespace Tarkontech\SivarCart\Admin\Pages;

use Illuminate\Http\Request;
use Tarkontech\SivarCart\Admin\AdminPage;
use Tarkontech\SivarCart\Admin\AdminPageProps;
use Tarkontech\SivarCart\Admin\AdminPresenter;
use Tarkontech\SivarCart\Models\Customer;
use Tarkontech\SivarCart\Services\Admin\CustomerService;

/**
 * The data of the buyers of the panel.
 *
 * The staff of the store looks for a buyer by name, by email or by phone.
 */
final readonly class CustomersIndexProps implements AdminPageProps
{
    /**
     * @return array<string, mixed>
     */
    public function props(Request $request, AdminPage $page): array
    {
        $search = (string) $request->query('q', '');

        $customers = app(CustomerService::class)->paginate(
            search: $search,
            perPage: (int) config('sivarcart.admin.per_page', 25),
        );

        return [
            'customers' => AdminPresenter::paginate(
                $customers,
                fn (Customer $customer): array => AdminPresenter::customerListItem($customer),
            ),
            'filters' => ['q' => $search],
        ];
    }
}
