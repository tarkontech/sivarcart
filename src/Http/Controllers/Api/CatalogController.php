<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Http\Controllers\Api;

use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use SivarcartCore\SivarcartCore\Data\CatalogFilters;
use SivarcartCore\SivarcartCore\Enums\CatalogSort;
use SivarcartCore\SivarcartCore\Http\Requests\CatalogRequest;
use SivarcartCore\SivarcartCore\Http\Resources\BrandResource;
use SivarcartCore\SivarcartCore\Http\Resources\CategoryResource;
use SivarcartCore\SivarcartCore\Http\Resources\ProductResource;
use SivarcartCore\SivarcartCore\Models\Product;
use SivarcartCore\SivarcartCore\Services\CatalogService;

/**
 * Reads the catalog of the store.
 *
 * The catalog is public: anybody can read the products, the categories and
 * the brands of the store.
 */
final class CatalogController extends ApiController
{
    public function __construct(private readonly CatalogService $catalog)
    {
        //
    }

    /**
     * The products of the catalog that answer the filters.
     */
    public function index(CatalogRequest $request): AnonymousResourceCollection
    {
        $filters = new CatalogFilters(
            categoryId: $request->integer('category_id') ?: null,
            brandId: $request->integer('brand_id') ?: null,
            minPrice: $request->has('min_price') ? (float) $request->input('min_price') : null,
            maxPrice: $request->has('max_price') ? (float) $request->input('max_price') : null,
            inStockOnly: $request->boolean('in_stock'),
            search: $request->string('search')->toString() ?: null,
            sort: $this->sort($request->string('sort')->toString()),
            perPage: $request->integer('per_page', 15),
            page: $request->integer('page', 1),
        );

        return ProductResource::collection($this->catalog->search($filters));
    }

    /**
     * A product of the catalog.
     */
    public function show(string $product): ProductResource
    {
        $model = Product::query()
            ->published()
            ->where('uuid', $product)
            ->with(['brand', 'categories', 'images', 'variants.optionValues'])
            ->firstOrFail();

        return ProductResource::make($model);
    }

    /**
     * The categories a shopper can browse.
     */
    public function categories(): AnonymousResourceCollection
    {
        return CategoryResource::collection($this->catalog->categories());
    }

    /**
     * The brands of the catalog.
     */
    public function brands(): AnonymousResourceCollection
    {
        return BrandResource::collection($this->catalog->brands());
    }

    /**
     * The order the shopper asked for.
     */
    private function sort(string $sort): CatalogSort
    {
        return CatalogSort::tryFrom($sort) ?? CatalogSort::RECENT;
    }
}
