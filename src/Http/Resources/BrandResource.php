<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use SivarcartCore\SivarcartCore\Models\Brand;

/**
 * A brand of the catalog, as the store API shows it.
 */
final class BrandResource extends JsonResource
{
    /**
     * The brand as the API shows it.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Brand $brand */
        $brand = $this->resource;

        return [
            'id' => $brand->getKey(),
            'name' => $brand->name,
            'slug' => $brand->slug,
            'description' => $brand->description,
            'logo_path' => $brand->logo_path,
            'logo_url' => $brand->logo_path === null ? null : $this->urlOf($brand),
            'position' => (int) $brand->position,
        ];
    }

    /**
     * The address of the logo of the brand.
     */
    private function urlOf(Brand $brand): ?string
    {
        $logo = $brand->getFirstMedia('logo');

        return $logo?->getUrl();
    }
}
