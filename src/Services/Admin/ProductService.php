<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Services\Admin;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use SivarcartCore\SivarcartCore\Models\Product;

/**
 * The catalog the staff of the store sees.
 *
 * The catalog of the store shows the published products only, because that is
 * what the shopper buys. The panel shows every product, the drafts included,
 * so the staff can work on a product that is not published yet.
 */
final class ProductService
{
    /**
     * The products of the catalog, with their brand and their variants.
     *
     * @return LengthAwarePaginator<int, Product>
     */
    public function paginate(string $search = '', int $perPage = 25): LengthAwarePaginator
    {
        return Product::query()
            ->with(['brand', 'variants'])
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('name', 'like', '%'.$search.'%')
                        ->orWhere('slug', 'like', '%'.$search.'%');
                });
            })
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    /**
     * One product of the catalog, by its uuid.
     */
    public function find(string $uuid): ?Product
    {
        return Product::query()
            ->with(['brand', 'categories', 'variants', 'images'])
            ->where('uuid', $uuid)
            ->first();
    }
}
