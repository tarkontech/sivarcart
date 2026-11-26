<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Admin\Pages;

use Illuminate\Http\Request;
use SivarcartCore\SivarcartCore\Admin\AdminPage;
use SivarcartCore\SivarcartCore\Admin\AdminPageProps;
use SivarcartCore\SivarcartCore\Admin\AdminPresenter;
use SivarcartCore\SivarcartCore\Models\Brand;
use SivarcartCore\SivarcartCore\Services\Admin\BrandService;
use SivarcartCore\SivarcartCore\Support\Auth;

/**
 * The data of the brands of the panel.
 */
final readonly class BrandsIndexProps implements AdminPageProps
{
    /**
     * @return array<string, mixed>
     */
    public function props(Request $request, AdminPage $page): array
    {
        $search = (string) $request->query('q', '');

        $brands = app(BrandService::class)->paginate(
            search: $search,
            perPage: (int) config('sivarcart-core.admin.per_page', 25),
        );

        return [
            'brands' => AdminPresenter::paginate(
                $brands,
                fn (Brand $brand): array => AdminPresenter::brand($brand),
            ),
            'filters' => ['q' => $search],
            'canUpdate' => Auth::hasStorePermission(Auth::user(), 'catalog.update'),
        ];
    }
}
