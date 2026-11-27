<?php

declare(strict_types=1);

namespace Tarkontech\SivarCart\Admin\Pages;

use Illuminate\Http\Request;
use Tarkontech\SivarCart\Admin\AdminPage;
use Tarkontech\SivarCart\Admin\AdminPageProps;
use Tarkontech\SivarCart\Admin\AdminPresenter;
use Tarkontech\SivarCart\Models\ShippingMethod;
use Tarkontech\SivarCart\Services\Admin\ShippingMethodService;
use Tarkontech\SivarCart\Support\Auth;

/**
 * The data of the shipping methods of the panel.
 */
final readonly class ShippingMethodsIndexProps implements AdminPageProps
{
    /**
     * @return array<string, mixed>
     */
    public function props(Request $request, AdminPage $page): array
    {
        $search = (string) $request->query('q', '');

        $methods = app(ShippingMethodService::class)->paginate(
            search: $search,
            perPage: (int) config('sivarcart.admin.per_page', 25),
        );

        return [
            'methods' => AdminPresenter::paginate(
                $methods,
                fn (ShippingMethod $method): array => AdminPresenter::shippingMethod($method),
            ),
            'filters' => ['q' => $search],
            'canUpdate' => Auth::hasStorePermission(Auth::user(), 'catalog.update'),
        ];
    }
}
