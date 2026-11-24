<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Data;

use SivarcartCore\SivarcartCore\Enums\CatalogSort;

/**
 * The way the shopper reads the catalog.
 *
 * The price and the stock of a product live in its variants, so the filters of
 * the price and of the availability look at the active variants of the
 * product: a product is in the result when it has at least one variant that
 * answers the filter.
 */
final class CatalogFilters
{
    /**
     * @param  int|null  $categoryId  The category whose products are read, including the ones of its subcategories.
     * @param  int|null  $brandId  The brand whose products are read.
     * @param  float|null  $minPrice  The lowest price a variant may have.
     * @param  float|null  $maxPrice  The highest price a variant may have.
     * @param  bool  $inStockOnly  Whether only the products with units available are read.
     * @param  string|null  $search  The text the name of the product or the sku of its variant may contain.
     * @param  CatalogSort  $sort  The way the products are sorted.
     * @param  int  $perPage  How many products each page holds.
     * @param  int  $page  The page that is read.
     */
    public function __construct(
        public readonly ?int $categoryId = null,
        public readonly ?int $brandId = null,
        public readonly ?float $minPrice = null,
        public readonly ?float $maxPrice = null,
        public readonly bool $inStockOnly = false,
        public readonly ?string $search = null,
        public readonly CatalogSort $sort = CatalogSort::RECENT,
        public readonly int $perPage = 15,
        public readonly int $page = 1,
    ) {
        //
    }
}
