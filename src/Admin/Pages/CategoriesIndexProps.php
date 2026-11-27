<?php

declare(strict_types=1);

namespace Tarkontech\SivarCart\Admin\Pages;

use Illuminate\Http\Request;
use Tarkontech\SivarCart\Admin\AdminPage;
use Tarkontech\SivarCart\Admin\AdminPageProps;
use Tarkontech\SivarCart\Admin\AdminPresenter;
use Tarkontech\SivarCart\Models\Category;
use Tarkontech\SivarCart\Services\Admin\CategoryService;
use Tarkontech\SivarCart\Support\Auth;

/**
 * The data of the categories of the panel.
 *
 * The staff of the store files the products of the catalog under categories,
 * so it writes the categories too.
 */
final readonly class CategoriesIndexProps implements AdminPageProps
{
    /**
     * @return array<string, mixed>
     */
    public function props(Request $request, AdminPage $page): array
    {
        $search = (string) $request->query('q', '');

        $categories = app(CategoryService::class)->paginate(
            search: $search,
            perPage: (int) config('sivarcart.admin.per_page', 25),
        );

        return [
            'categories' => AdminPresenter::paginate(
                $categories,
                fn (Category $category): array => AdminPresenter::category($category),
            ),
            'filters' => ['q' => $search],
            'canUpdate' => Auth::hasStorePermission(Auth::user(), 'catalog.update'),
        ];
    }
}
