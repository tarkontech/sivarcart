<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use SivarcartCore\SivarcartCore\Data\CatalogFilters;
use SivarcartCore\SivarcartCore\Enums\CatalogSort;
use SivarcartCore\SivarcartCore\Models\Category;
use SivarcartCore\SivarcartCore\Models\Product;
use SivarcartCore\SivarcartCore\Models\ProductVariant;

/**
 * Reads the catalog of the store.
 *
 * The price and the stock of a product live in its variants, so the filters of
 * the price and of the availability look at the active variants of the
 * product: a product is in the result when it has at least one variant that
 * answers the filter. The products sorted by price are sorted by the cheapest
 * variant they have.
 *
 * The service only reads: it never writes, it never locks and it never keeps
 * a cache of the catalog.
 */
final class CatalogService
{
    /**
     * The most products a page can hold.
     */
    private const int MAX_PER_PAGE = 100;

    /**
     * The products of the catalog that answer the filters.
     *
     * @return LengthAwarePaginator<int, Product>
     */
    public function search(CatalogFilters $filters): LengthAwarePaginator
    {
        $query = Product::query()->published();

        // A product that has no active variant has nothing to sell, so it is
        // never part of the catalog.
        $query->whereHas('variants', fn (Builder $query): Builder => $query->active());

        $this->filterByCategory($query, $filters->categoryId);
        $this->filterByBrand($query, $filters->brandId);
        $this->filterByPrice($query, $filters->minPrice, $filters->maxPrice);
        $this->filterByAvailability($query, $filters->inStockOnly);
        $this->filterByText($query, $filters->search);

        $this->sort($query, $filters->sort);

        $query->with([
            'brand',
            'variants' => function (Relation $relation): void {
                $relation->getQuery()->active()->orderBy('price');
            },
        ]);

        return $query->paginate(
            max(1, min($filters->perPage, self::MAX_PER_PAGE)),
            ['*'],
            'page',
            max(1, $filters->page),
        );
    }

    /**
     * Keep the products filed under the category and under its subcategories.
     *
     * @param  Builder<Product>  $query
     */
    private function filterByCategory(Builder $query, ?int $categoryId): void
    {
        if ($categoryId === null) {
            return;
        }

        $ids = $this->categoryIds($categoryId);

        $query->whereHas('categories', function (Builder $query) use ($ids): Builder {
            return $query->whereIn('sivarcart_categories.id', $ids);
        });
    }

    /**
     * Keep the products of the brand.
     *
     * @param  Builder<Product>  $query
     */
    private function filterByBrand(Builder $query, ?int $brandId): void
    {
        if ($brandId === null) {
            return;
        }

        $query->where('brand_id', $brandId);
    }

    /**
     * Keep the products with an active variant inside the range of the price.
     *
     * @param  Builder<Product>  $query
     */
    private function filterByPrice(Builder $query, ?float $minPrice, ?float $maxPrice): void
    {
        if ($minPrice === null && $maxPrice === null) {
            return;
        }

        $query->whereHas('variants', function (Builder $query) use ($minPrice, $maxPrice): Builder {
            return $query->active()
                ->when(
                    $minPrice !== null,
                    fn (Builder $query): Builder => $query->where('price', '>=', $minPrice),
                )
                ->when(
                    $maxPrice !== null,
                    fn (Builder $query): Builder => $query->where('price', '<=', $maxPrice),
                );
        });
    }

    /**
     * Keep the products with an active variant that still has units available.
     *
     * @param  Builder<Product>  $query
     */
    private function filterByAvailability(Builder $query, bool $inStockOnly): void
    {
        if (! $inStockOnly) {
            return;
        }

        $query->whereHas('variants', function (Builder $query): Builder {
            return $query->active()->whereColumn(
                'sivarcart_product_variants.stock_quantity',
                '>',
                'sivarcart_product_variants.reserved_quantity',
            );
        });
    }

    /**
     * Keep the products whose name or whose variant sku hold the text.
     *
     * @param  Builder<Product>  $query
     */
    private function filterByText(Builder $query, ?string $search): void
    {
        if ($search === null) {
            return;
        }

        $term = trim($search);

        if ($term === '') {
            return;
        }

        $pattern = '%'.$term.'%';

        $query->where(function (Builder $query) use ($pattern): Builder {
            return $query->where('name', 'like', $pattern)
                ->orWhereHas('variants', function (Builder $query) use ($pattern): Builder {
                    return $query->where('sku', 'like', $pattern);
                });
        });
    }

    /**
     * Sort the products the way the shopper asked for.
     *
     * @param  Builder<Product>  $query
     */
    private function sort(Builder $query, CatalogSort $sort): void
    {
        match ($sort) {
            CatalogSort::RECENT => $query->orderByDesc('published_at')->orderByDesc('id'),
            CatalogSort::NAME => $query->orderBy('name')->orderBy('id'),
            CatalogSort::PRICE_ASC => $this->sortByPrice($query, 'asc'),
            CatalogSort::PRICE_DESC => $this->sortByPrice($query, 'desc'),
        };
    }

    /**
     * Sort the products by the price of their cheapest active variant.
     *
     * The price is read with a subquery, so the products are sorted by the
     * price they are really sold at and not by the price of one of their
     * variants.
     *
     * @param  Builder<Product>  $query
     * @param  'asc'|'desc'  $direction
     */
    private function sortByPrice(Builder $query, string $direction): void
    {
        $price = ProductVariant::query()
            ->select('price')
            ->whereColumn('sivarcart_product_variants.product_id', 'sivarcart_products.id')
            ->where('is_active', true)
            ->orderBy('price')
            ->limit(1)
            ->toBase();

        $query->orderBy($price, $direction)->orderBy('id');
    }

    /**
     * The category and the categories nested under it.
     *
     * The categories are walked by levels, and the ones already visited are
     * left aside, so a category that points at itself never loops forever.
     *
     * @return list<int>
     */
    private function categoryIds(int $categoryId): array
    {
        $ids = [$categoryId];
        $frontier = [$categoryId];
        $visited = [$categoryId => true];

        while ($frontier !== []) {
            $children = Category::query()
                ->whereIn('parent_id', $frontier)
                ->pluck('id')
                ->map(fn (mixed $id): int => (int) $id)
                ->all();

            $frontier = [];

            foreach ($children as $child) {
                if (isset($visited[$child])) {
                    continue;
                }

                $visited[$child] = true;
                $ids[] = $child;
                $frontier[] = $child;
            }
        }

        return $ids;
    }
}
