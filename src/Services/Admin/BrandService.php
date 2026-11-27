<?php

declare(strict_types=1);

namespace Tarkontech\SivarCart\Services\Admin;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Tarkontech\SivarCart\Models\Brand;
use Tarkontech\SivarCart\Support\Slug;

/**
 * The brands of the catalog, as the staff of the store sees them.
 *
 * A brand is only a name and a logo: the products of the catalog point at it,
 * and the shopper browses them by it.
 */
final class BrandService
{
    /**
     * The brands of the catalog.
     *
     * @return LengthAwarePaginator<int, Brand>
     */
    public function paginate(string $search = '', int $perPage = 25): LengthAwarePaginator
    {
        return Brand::query()
            ->withCount('products')
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('name', 'like', '%'.$search.'%')
                        ->orWhere('slug', 'like', '%'.$search.'%');
                });
            })
            ->orderBy('position')
            ->orderBy('name')
            ->paginate($perPage);
    }

    /**
     * One brand of the catalog, by its identifier.
     *
     * The brands of the catalog are addressed by their identifier and not by
     * a uuid, the same way the store API addresses them.
     */
    public function find(int $id): ?Brand
    {
        return Brand::query()->find($id);
    }

    /**
     * Every brand of the catalog.
     *
     * @return list<Brand>
     */
    public function all(): array
    {
        return array_values(
            Brand::query()
                ->orderBy('position')
                ->orderBy('name')
                ->get()
                ->all(),
        );
    }

    /**
     * Create a brand of the catalog.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): Brand
    {
        $brand = new Brand;

        $brand->fill($this->attributes($attributes, null));
        $brand->save();

        return $brand->refresh();
    }

    /**
     * Write a brand of the catalog.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function update(Brand $brand, array $attributes): Brand
    {
        $brand->fill($this->attributes($attributes, $brand));
        $brand->save();

        return $brand->refresh();
    }

    /**
     * Remove a brand of the catalog.
     *
     * The products that carried it keep living in the catalog: they are left
     * without a brand.
     */
    public function delete(Brand $brand): void
    {
        $brand->products()->update(['brand_id' => null]);

        $brand->delete();
    }

    /**
     * The attributes of a brand, ready to be filled.
     *
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function attributes(array $attributes, ?Brand $brand): array
    {
        $name = trim((string) ($attributes['name'] ?? ''));

        $slug = trim((string) ($attributes['slug'] ?? ''));

        if ($slug === '') {
            $slug = Slug::unique($name, 'sivarcart_brands', $brand?->getKey());
        }

        return [
            'name' => $name,
            'slug' => $slug,
            'description' => $this->text($attributes['description'] ?? null),
            'position' => (int) ($attributes['position'] ?? 0),
            'is_active' => (bool) ($attributes['is_active'] ?? true),
        ];
    }

    /**
     * A text that is not an empty string.
     */
    private function text(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }
}
