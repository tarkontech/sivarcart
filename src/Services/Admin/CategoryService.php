<?php

declare(strict_types=1);

namespace Tarkontech\SivarCart\Services\Admin;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Tarkontech\SivarCart\Exceptions\InvalidCategoryParent;
use Tarkontech\SivarCart\Models\Category;
use Tarkontech\SivarCart\Support\Slug;

/**
 * The categories of the catalog, as the staff of the store sees them.
 *
 * The categories form a tree, and the tree is what the shopper browses. A
 * category that is filed under itself is a loop, so the service refuses to
 * write it.
 */
final class CategoryService
{
    /**
     * The categories of the catalog.
     *
     * @return LengthAwarePaginator<int, Category>
     */
    public function paginate(string $search = '', int $perPage = 25): LengthAwarePaginator
    {
        return Category::query()
            ->with('parent')
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
     * One category of the catalog, by its uuid.
     */
    public function find(string $uuid): ?Category
    {
        return Category::query()
            ->with(['parent', 'children'])
            ->where('uuid', $uuid)
            ->first();
    }

    /**
     * Every category of the catalog, flat.
     *
     * The list comes with the id of the parent, so the panel can draw the
     * tree it needs.
     *
     * @return list<Category>
     */
    public function all(): array
    {
        return array_values(
            Category::query()
                ->orderBy('position')
                ->orderBy('name')
                ->get()
                ->all(),
        );
    }

    /**
     * Create a category of the catalog.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): Category
    {
        $category = new Category;

        $category->fill($this->attributes($attributes, null));
        $category->save();

        return $category->refresh();
    }

    /**
     * Write a category of the catalog.
     *
     * @param  array<string, mixed>  $attributes
     *
     * @throws InvalidCategoryParent
     */
    public function update(Category $category, array $attributes): Category
    {
        $parent = $this->id($attributes['parent_id'] ?? null);

        if ($parent !== null && $this->isInside($category, $parent)) {
            throw InvalidCategoryParent::cycle();
        }

        $category->fill($this->attributes($attributes, $category));
        $category->save();

        return $category->refresh();
    }

    /**
     * Take a category off the catalog.
     *
     * The products that were filed under it are not erased: they stay in the
     * catalog, without a category.
     */
    public function delete(Category $category): void
    {
        $category->products()->detach();

        $category->delete();
    }

    /**
     * The attributes of a category, ready to be filled.
     *
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function attributes(array $attributes, ?Category $category): array
    {
        $name = trim((string) ($attributes['name'] ?? ''));

        $slug = trim((string) ($attributes['slug'] ?? ''));

        if ($slug === '') {
            $slug = Slug::unique($name, 'sivarcart_categories', $category?->getKey());
        }

        return [
            'name' => $name,
            'slug' => $slug,
            'description' => $this->text($attributes['description'] ?? null),
            'icon' => $this->text($attributes['icon'] ?? null),
            'position' => (int) ($attributes['position'] ?? 0),
            'is_active' => (bool) ($attributes['is_active'] ?? true),
            'is_featured' => (bool) ($attributes['is_featured'] ?? false),
            'parent_id' => $this->id($attributes['parent_id'] ?? null),
        ];
    }

    /**
     * Whether a category is inside another one.
     *
     * The tree is walked from the parent that was asked for towards its
     * ancestors, and the ones already visited are left aside, so a category
     * that points at itself never loops forever. A category that is an
     * ancestor of the parent would become a child of itself, so the parent
     * is refused.
     */
    private function isInside(Category $category, int $parentId): bool
    {
        $current = $parentId;
        $visited = [];

        while ($current !== null && ! isset($visited[$current])) {
            if ($current === $category->getKey()) {
                return true;
            }

            $visited[$current] = true;

            $parent = Category::query()->whereKey($current)->value('parent_id');

            $current = $parent === null ? null : (int) $parent;
        }

        return false;
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
}
