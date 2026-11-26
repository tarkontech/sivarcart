<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Admin\Pages;

use Illuminate\Http\Request;
use SivarcartCore\SivarcartCore\Admin\AdminPage;
use SivarcartCore\SivarcartCore\Admin\AdminPageProps;
use SivarcartCore\SivarcartCore\Admin\AdminPresenter;
use SivarcartCore\SivarcartCore\Models\PaymentMethod;
use SivarcartCore\SivarcartCore\Services\Admin\PaymentMethodService;
use SivarcartCore\SivarcartCore\Support\Auth;

/**
 * The data of the payment methods of the panel.
 */
final readonly class PaymentMethodsIndexProps implements AdminPageProps
{
    /**
     * @return array<string, mixed>
     */
    public function props(Request $request, AdminPage $page): array
    {
        $search = (string) $request->query('q', '');

        $methods = app(PaymentMethodService::class)->paginate(
            search: $search,
            perPage: (int) config('sivarcart-core.admin.per_page', 25),
        );

        return [
            'methods' => AdminPresenter::paginate(
                $methods,
                fn (PaymentMethod $method): array => AdminPresenter::paymentMethod($method),
            ),
            'filters' => ['q' => $search],
            'canUpdate' => Auth::hasStorePermission(Auth::user(), 'catalog.update'),
        ];
    }
}
