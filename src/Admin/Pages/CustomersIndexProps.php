<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Admin\Pages;

use Illuminate\Http\Request;
use SivarcartCore\SivarcartCore\Admin\AdminPage;
use SivarcartCore\SivarcartCore\Admin\AdminPageProps;
use SivarcartCore\SivarcartCore\Admin\AdminPresenter;
use SivarcartCore\SivarcartCore\Models\Customer;
use SivarcartCore\SivarcartCore\Services\Admin\CustomerService;

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
            perPage: (int) config('sivarcart-core.admin.per_page', 25),
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
