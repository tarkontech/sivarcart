<?php

declare(strict_types=1);

namespace Tarkontech\SivarCart\Http\Controllers\Storefront;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Tarkontech\SivarCart\Data\CatalogFilters;
use Tarkontech\SivarCart\Enums\CatalogSort;
use Tarkontech\SivarCart\Http\Requests\CatalogRequest;
use Tarkontech\SivarCart\Models\Product;
use Tarkontech\SivarCart\Services\CatalogService;
use Tarkontech\SivarCart\Storefront\StorefrontPresenter;

/**
 * The catalog the shopper browses.
 *
 * The catalog is public: anybody can read the products, the categorías y las
 * marcas de la tienda.
 */
final class CatalogController
{
    public function __construct(private readonly CatalogService $catalog)
    {
        //
    }

    /**
     * The home of the store.
     *
     * The home shows lo que la tienda quiere mostrar: los productos
     * destacados y las categorías con las que el comprador empieza.
     */
    public function home(Request $request): Response
    {
        $featured = $this->catalog->search(new CatalogFilters(
            perPage: (int) config('sivarcart.storefront.per_page', 15),
        ));

        return Inertia::render('Storefront/Home', [
            'products' => StorefrontPresenter::paginate(
                $featured,
                fn (Product $product): array => StorefrontPresenter::productCard($product),
            ),
            'featured' => $this->featured(),
        ]);
    }

    /**
     * The catalog with the filters the shopper asked for.
     */
    public function index(CatalogRequest $request): Response
    {
        $filters = $this->filters($request);

        return Inertia::render('Storefront/Catalog/Index', [
            'products' => StorefrontPresenter::paginate(
                $this->catalog->search($filters),
                fn (Product $product): array => StorefrontPresenter::productCard($product),
            ),
            'filters' => [
                'category_id' => $filters->categoryId,
                'brand_id' => $filters->brandId,
                'min_price' => $filters->minPrice,
                'max_price' => $filters->maxPrice,
                'in_stock' => $filters->inStockOnly,
                'search' => $filters->search,
                'sort' => $filters->sort->value,
            ],
            'sorts' => $this->sorts(),
        ]);
    }

    /**
     * The page of a product.
     */
    public function show(string $product): Response
    {
        $model = Product::query()
            ->published()
            ->where('uuid', $product)
            ->with(['brand', 'categories', 'images', 'variants.optionValues'])
            ->firstOrFail();

        return Inertia::render('Storefront/Products/Show', [
            'product' => StorefrontPresenter::productDetail($model),
        ]);
    }

    /**
     * The filters the catalog answers with.
     */
    private function filters(CatalogRequest $request): CatalogFilters
    {
        return new CatalogFilters(
            categoryId: $request->integer('category_id') ?: null,
            brandId: $request->integer('brand_id') ?: null,
            minPrice: $request->has('min_price') ? (float) $request->input('min_price') : null,
            maxPrice: $request->has('max_price') ? (float) $request->input('max_price') : null,
            inStockOnly: $request->boolean('in_stock'),
            search: $request->string('search')->toString() ?: null,
            sort: CatalogSort::tryFrom($request->string('sort')->toString()) ?? CatalogSort::RECENT,
            perPage: (int) config('sivarcart.storefront.per_page', 15),
            page: max(1, $request->integer('page', 1)),
        );
    }

    /**
     * The products that the store quiere mostrar en la portada.
     *
     * @return list<array<string, mixed>>
     */
    private function featured(): array
    {
        $products = Product::query()
            ->published()
            ->where('is_featured', true)
            ->whereHas('variants', fn (Builder $query): Builder => $query->active())
            ->with(['brand', 'variants' => function (Relation $relation): void {
                $relation->getQuery()->active()->orderBy('price');
            }])
            ->orderByDesc('id')
            ->limit((int) config('sivarcart.storefront.per_page', 15))
            ->get();

        $featured = [];

        foreach ($products as $product) {
            $featured[] = StorefrontPresenter::productCard($product);
        }

        return $featured;
    }

    /**
     * The ways the shopper can sort the catalog.
     *
     * @return list<array{value: string, label: string}>
     */
    private function sorts(): array
    {
        $sorts = [];

        foreach (CatalogSort::cases() as $sort) {
            $sorts[] = [
                'value' => $sort->value,
                'label' => $sort->label(),
            ];
        }

        return $sorts;
    }
}
