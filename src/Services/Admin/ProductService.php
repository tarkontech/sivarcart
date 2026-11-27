<?php

declare(strict_types=1);

namespace Tarkontech\SivarCart\Services\Admin;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Tarkontech\SivarCart\Enums\ProductStatus;
use Tarkontech\SivarCart\Enums\ProductType;
use Tarkontech\SivarCart\Models\Product;
use Tarkontech\SivarCart\Models\ProductVariant;
use Tarkontech\SivarCart\Support\Slug;

/**
 * The catalog the staff of the store sees.
 *
 * The catalog of the store shows the published products only, because that is
 * what the shopper buys. The panel shows every product, the drafts included,
 * so the staff can work on a product that is not published yet.
 *
 * The service also writes the catalog, because the panel never writes the
 * domain by itself: the price and the stock live in the variants, so a
 * product and its variants are always written together.
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

    /**
     * Create a product of the catalog.
     *
     * The address of the product is built from its name, and the variants
     * that come with it are written with it: a product without a sellable
     * unit is not a product of the store.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): Product
    {
        $product = new Product;

        $product->fill($this->attributes($attributes, null));
        $product->published_at = $this->publishedAt($this->statusOf($attributes), null);
        $product->save();

        $this->syncVariants($product, $this->variantsOf($attributes));

        if (is_array($attributes['categories'] ?? null)) {
            $product->categories()->sync($this->categoryIds($attributes['categories']));
        }

        return $product->refresh();
    }

    /**
     * Write a product of the catalog.
     *
     * The variants that the staff took away from the form are removed, and the
     * ones that are still there are written. A removed variant keeps its row:
     * the orders that were placed with it remember what was bought.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function update(Product $product, array $attributes): Product
    {
        $product->fill($this->attributes($attributes, $product));
        $product->published_at = $this->publishedAt($this->statusOf($attributes), $product);
        $product->save();

        $this->syncVariants($product, $this->variantsOf($attributes));

        if (is_array($attributes['categories'] ?? null)) {
            $product->categories()->sync($this->categoryIds($attributes['categories']));
        }

        return $product->refresh();
    }

    /**
     * Take a product off the catalog.
     *
     * The product is not erased: the orders that were placed with it keep
     * pointing at it, and the staff can still read it.
     */
    public function delete(Product $product): void
    {
        $product->delete();
    }

    /**
     * The attributes of a product, ready to be filled.
     *
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function attributes(array $attributes, ?Product $product): array
    {
        $name = trim((string) ($attributes['name'] ?? ''));

        $slug = trim((string) ($attributes['slug'] ?? ''));

        if ($slug === '') {
            $slug = Slug::unique($name, 'sivarcart_products', $product?->getKey());
        }

        $status = $this->statusOf($attributes);

        $type = ProductType::tryFrom((string) ($attributes['type'] ?? '')) ?? ProductType::SIMPLE;

        return [
            'name' => $name,
            'slug' => $slug,
            'type' => $type->value,
            'status' => $status->value,
            'short_description' => $this->text($attributes['short_description'] ?? null),
            'description' => $this->text($attributes['description'] ?? null),
            'is_featured' => (bool) ($attributes['is_featured'] ?? false),
            'is_virtual' => (bool) ($attributes['is_virtual'] ?? false),
            'brand_id' => $this->id($attributes['brand_id'] ?? null),
            'weight' => $this->decimal($attributes['weight'] ?? null),
        ];
    }

    /**
     * The state of publication of a product.
     *
     * @param  array<string, mixed>  $attributes
     */
    private function statusOf(array $attributes): ProductStatus
    {
        return ProductStatus::tryFrom((string) ($attributes['status'] ?? '')) ?? ProductStatus::DRAFT;
    }

    /**
     * The moment the product was published.
     *
     * A product that is published for the first time is published now. A
     * product that goes back to the draft is not published anymore, and a
     * product that is only archived keeps the day it was published.
     */
    private function publishedAt(ProductStatus $status, ?Product $product): ?Carbon
    {
        if ($status === ProductStatus::PUBLISHED) {
            if ($product instanceof Product && $product->published_at !== null) {
                return $product->published_at;
            }

            return Carbon::now();
        }

        if ($status === ProductStatus::DRAFT) {
            return null;
        }

        if ($product instanceof Product) {
            return $product->published_at;
        }

        return null;
    }

    /**
     * The variants the form carries.
     *
     * @param  array<string, mixed>  $attributes
     * @return list<array<string, mixed>>
     */
    private function variantsOf(array $attributes): array
    {
        $variants = $attributes['variants'] ?? null;

        if (! is_array($variants)) {
            return [];
        }

        $list = [];

        foreach ($variants as $variant) {
            if (is_array($variant)) {
                $list[] = $variant;
            }
        }

        return $list;
    }

    /**
     * Write the variants of a product.
     *
     * The variants that come with the uuid of a variant of the product are
     * written; the ones without a uuid are new; the ones that are not in the
     * list anymore are removed.
     *
     * @param  list<array<string, mixed>>  $variants
     */
    private function syncVariants(Product $product, array $variants): void
    {
        if ($product->type === ProductType::SIMPLE) {
            $this->syncTheOnlyVariant($product, $variants);

            return;
        }

        $existing = $product->variants()->orderBy('id')->get()->keyBy('uuid');

        $kept = [];

        foreach ($variants as $attributes) {
            $uuid = $attributes['uuid'] ?? null;

            $variant = is_string($uuid) ? $existing->get($uuid) : null;

            if (! $variant instanceof ProductVariant) {
                $variant = $product->variants()->make();
            }

            $this->fillVariant($variant, $attributes);

            $kept[] = $variant->getKey();
        }

        $product->variants()
            ->whereNotIn('id', $kept)
            ->get()
            ->each(fn (ProductVariant $variant) => $variant->delete());

        $this->ensureOneDefault($product);
    }

    /**
     * Write the only variant of a simple product.
     *
     * A simple product owns exactly one sellable unit, and the product brings
     * one with it, so the variant of the form is written on it and the other
     * ones are removed.
     *
     * @param  list<array<string, mixed>>  $variants
     */
    private function syncTheOnlyVariant(Product $product, array $variants): void
    {
        $variant = $product->variants()->orderBy('id')->first();

        if (! $variant instanceof ProductVariant) {
            $variant = $product->variants()->make();
        }

        if ($variants !== []) {
            $this->fillVariant($variant, $variants[0]);
        }

        $variant->is_default = true;
        $variant->save();

        $product->variants()
            ->where('id', '!=', $variant->getKey())
            ->get()
            ->each(fn (ProductVariant $other) => $other->delete());
    }

    /**
     * Write a variant of a product.
     *
     * @param  array<string, mixed>  $attributes
     */
    private function fillVariant(ProductVariant $variant, array $attributes): void
    {
        $variant->fill([
            'name' => $this->text($attributes['name'] ?? null),
            'sku' => $this->text($attributes['sku'] ?? null),
            'barcode' => $this->text($attributes['barcode'] ?? null),
            'price' => $this->decimal($attributes['price'] ?? 0) ?? 0,
            'compare_at_price' => $this->decimal($attributes['compare_at_price'] ?? null),
            'cost_price' => $this->decimal($attributes['cost_price'] ?? null),
            'stock_quantity' => (int) ($attributes['stock_quantity'] ?? 0),
            'low_stock_threshold' => $this->integer($attributes['low_stock_threshold'] ?? null),
            'is_default' => (bool) ($attributes['is_default'] ?? false),
            'is_active' => (bool) ($attributes['is_active'] ?? true),
            'weight' => $this->decimal($attributes['weight'] ?? null),
        ]);
    }

    /**
     * Make sure the product has exactly one default variant.
     *
     * The default variant is the one the shopper buys when they do not pick
     * one, so a product always has one.
     */
    private function ensureOneDefault(Product $product): void
    {
        $variants = $product->variants()->orderBy('id')->get();

        if ($variants->isEmpty()) {
            return;
        }

        $default = $variants->firstWhere('is_default', true) ?? $variants->first();

        $product->variants()
            ->where('id', '!=', $default->getKey())
            ->update(['is_default' => false]);

        $product->variants()
            ->whereKey($default->getKey())
            ->update(['is_default' => true]);
    }

    /**
     * The categories a product is filed under.
     *
     * @param  array<int, mixed>  $categories
     * @return list<int>
     */
    private function categoryIds(array $categories): array
    {
        $ids = [];

        foreach ($categories as $category) {
            $id = $this->id($category);

            if ($id !== null) {
                $ids[] = $id;
            }
        }

        return array_values(array_unique($ids));
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

    /**
     * An identifier that is not empty.
     */
    private function id(mixed $value): ?int
    {
        if (is_int($value)) {
            return $value;
        }

        if (is_string($value) && trim($value) !== '') {
            return (int) $value;
        }

        return null;
    }

    /**
     * A number with two decimals.
     */
    private function decimal(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (! is_numeric($value)) {
            return null;
        }

        return round((float) $value, 2);
    }

    /**
     * A whole number.
     */
    private function integer(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (! is_numeric($value)) {
            return null;
        }

        return (int) $value;
    }
}
