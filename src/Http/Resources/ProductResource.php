<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use SivarcartCore\SivarcartCore\Models\Product;
use SivarcartCore\SivarcartCore\Models\ProductImage;

/**
 * A product of the catalog, as the store API shows it.
 *
 * The price and the stock of a product live in its variants, so the product
 * carries the range of prices and the variants that a shopper can pick.
 */
final class ProductResource extends JsonResource
{
    /**
     * The product as the API shows it.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Product $product */
        $product = $this->resource;

        return [
            'id' => $product->uuid,
            'name' => $product->name,
            'slug' => $product->slug,
            'type' => $product->type->value,
            'status' => $product->status->value,
            'short_description' => $product->short_description,
            'description' => $product->description,
            'is_featured' => (bool) $product->is_featured,
            'is_virtual' => (bool) $product->is_virtual,
            'thumbnail_path' => $product->thumbnail_path,
            'sku' => $product->sku(),
            'price_range' => $product->priceRange(),
            'brand' => $product->brand === null ? null : new BrandResource($product->brand),
            'categories' => CategoryResource::collection($this->whenLoaded('categories')),
            'variants' => ProductVariantResource::collection($product->variants),
            'images' => $this->whenLoaded('images', fn (): array => $product->images
                ->map(fn (ProductImage $image): array => [
                    'path' => $image->path,
                    'disk' => $image->disk,
                    'alt' => $image->alt,
                    'position' => (int) $image->position,
                    'is_primary' => (bool) $image->is_primary,
                ])
                ->all()),
        ];
    }
}
