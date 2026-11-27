<?php

declare(strict_types=1);

namespace Tarkontech\SivarCart\Admin\Pages;

use Illuminate\Http\Request;
use Tarkontech\SivarCart\Admin\AdminPage;
use Tarkontech\SivarCart\Admin\AdminPageProps;
use Tarkontech\SivarCart\Admin\AdminPresenter;
use Tarkontech\SivarCart\Models\Brand;
use Tarkontech\SivarCart\Services\Admin\BrandService;
use Tarkontech\SivarCart\Support\Auth;

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
            perPage: (int) config('sivarcart.admin.per_page', 25),
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
