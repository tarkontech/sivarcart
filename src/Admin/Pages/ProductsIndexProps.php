<?php

declare(strict_types=1);

namespace Tarkontech\SivarCart\Admin\Pages;

use Illuminate\Http\Request;
use Tarkontech\SivarCart\Admin\AdminPage;
use Tarkontech\SivarCart\Admin\AdminPageProps;
use Tarkontech\SivarCart\Admin\AdminPresenter;
use Tarkontech\SivarCart\Models\Product;
use Tarkontech\SivarCart\Services\Admin\ProductService;
use Tarkontech\SivarCart\Support\Auth;

/**
 * The data of the catalog of the panel.
 *
 * Unlike the catalog of the store, this one shows the drafts too: the staff
 * works on a product before it is published.
 */
final readonly class ProductsIndexProps implements AdminPageProps
{
    /**
     * @return array<string, mixed>
     */
    public function props(Request $request, AdminPage $page): array
    {
        $search = (string) $request->query('q', '');

        $products = app(ProductService::class)->paginate(
            search: $search,
            perPage: (int) config('sivarcart.admin.per_page', 25),
        );

        return [
            'products' => AdminPresenter::paginate(
                $products,
                fn (Product $product): array => AdminPresenter::productListItem($product),
            ),
            'filters' => ['q' => $search],
            'canUpdate' => Auth::hasStorePermission(Auth::user(), 'catalog.update'),
        ];
    }
}
