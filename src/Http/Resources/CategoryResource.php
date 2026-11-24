<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use SivarcartCore\SivarcartCore\Models\Category;

/**
 * A category of the catalog, as the store API shows it.
 */
final class CategoryResource extends JsonResource
{
    /**
     * The category as the API shows it.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Category $category */
        $category = $this->resource;

        return [
            'id' => $category->uuid,
            'name' => $category->name,
            'slug' => $category->slug,
            'description' => $category->description,
            'image_path' => $category->image_path,
            'icon' => $category->icon,
            'parent_id' => $category->parent_id,
            'is_featured' => (bool) $category->is_featured,
            'position' => (int) $category->position,
        ];
    }
}
