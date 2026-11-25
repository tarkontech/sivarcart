<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\File;
use SivarcartCore\SivarcartCore\Exceptions\InvalidMediaFile;
use SivarcartCore\SivarcartCore\Models\Brand;
use SivarcartCore\SivarcartCore\Models\Product;
use SivarcartCore\SivarcartCore\Models\ProductImage;
use SivarcartCore\SivarcartCore\Models\ProductVariant;
use SivarcartCore\SivarcartCore\Models\Store;
use SivarcartCore\SivarcartCore\Support\Media;
use Spatie\MediaLibrary\HasMedia;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Throwable;

/**
 * The images of the store.
 *
 * The service owns the domain of the media: it says what file the store
 * accepts, which product an image belongs to and which one is the main
 * image. The file itself, and the thumbnails that come out of it, are the
 * business of the media library of Spatie.
 *
 * Nothing is written halfway: when the store fails after the file is already
 * in the disk, the file is removed and the row is rolled back, so a rejected
 * image leaves nothing behind.
 */
final class MediaService
{
    /**
     * Store an image of a product.
     *
     * The image belongs to the product and, when it is the main one, it
     * replaces the main image the product had.
     */
    public function storeProductImage(
        Product $product,
        UploadedFile $file,
        ?string $alt = null,
        bool $isPrimary = false,
    ): ProductImage {
        $this->guard($file);

        $image = $this->store($product, $file, 'images', ['alt' => $alt], [
            'product_id' => $product->getKey(),
            'product_variant_id' => null,
            'alt' => $alt,
            'is_primary' => $isPrimary,
        ]);

        if ($isPrimary) {
            $this->demoteTheOthers($image);
            $this->refreshTheThumbnailOf($product);
        }

        return $image;
    }

    /**
     * Store an image of a variant.
     *
     * The image belongs to the variant and also to the product that owns it:
     * the gallery of a product holds the images of its variants.
     */
    public function storeVariantImage(
        ProductVariant $variant,
        UploadedFile $file,
        ?string $alt = null,
    ): ProductImage {
        $this->guard($file);

        return $this->store($variant, $file, 'images', ['alt' => $alt], [
            'product_id' => $variant->product_id,
            'product_variant_id' => $variant->getKey(),
            'alt' => $alt,
            'is_primary' => false,
        ]);
    }

    /**
     * Store the logo of a brand.
     *
     * The brand only has one logo: the new one replaces the old one.
     */
    public function storeBrandLogo(Brand $brand, UploadedFile $file): ProductImage
    {
        $this->guard($file);

        $image = $this->store($brand, $file, 'logo', [], [
            'product_id' => null,
            'product_variant_id' => null,
            'alt' => $brand->name,
            'is_primary' => false,
        ]);

        $brand->logo_path = $image->getPathRelativeToRoot();
        $brand->save();

        return $image;
    }

    /**
     * Store the logo of the store.
     */
    public function storeStoreLogo(Store $store, UploadedFile $file): ProductImage
    {
        $this->guard($file);

        $image = $this->store($store, $file, 'logo', [], [
            'product_id' => null,
            'product_variant_id' => null,
            'alt' => $store->name,
            'is_primary' => false,
        ]);

        $store->logo_path = $image->getPathRelativeToRoot();
        $store->save();

        return $image;
    }

    /**
     * Store the favicon of the store.
     */
    public function storeStoreFavicon(Store $store, UploadedFile $file): ProductImage
    {
        $this->guard($file);

        $image = $this->store($store, $file, 'favicon', [], [
            'product_id' => null,
            'product_variant_id' => null,
            'alt' => $store->name,
            'is_primary' => false,
        ]);

        $store->favicon_path = $image->getPathRelativeToRoot();
        $store->save();

        return $image;
    }

    /**
     * Make an image the main one of its product.
     */
    public function makePrimary(ProductImage $image): void
    {
        $this->demoteTheOthers($image);

        $image->is_primary = true;
        $image->save();

        $product = $image->product;

        if ($product !== null) {
            $this->refreshTheThumbnailOf($product);
        }
    }

    /**
     * Remove an image and the file that came with it.
     */
    public function delete(ProductImage $image): void
    {
        $product = $image->product;

        $image->delete();

        if ($product !== null) {
            $this->refreshTheThumbnailOf($product);
        }
    }

    /**
     * Store the file and write the columns of the domain.
     *
     * @param  array<string, mixed>  $customProperties
     * @param  array<string, mixed>  $domain
     */
    private function store(
        HasMedia $owner,
        UploadedFile $file,
        string $collection,
        array $customProperties,
        array $domain,
    ): ProductImage {
        $image = null;

        try {
            DB::transaction(function () use ($owner, $file, $collection, $customProperties, $domain, &$image): void {
                /** @var ProductImage $media */
                $media = $owner->addMedia($file)
                    ->withCustomProperties($customProperties)
                    ->withProperties($domain)
                    ->toMediaCollection($collection, Media::disk());

                // The path of the file depends on the id of the media, and the
                // media library writes the row before it knows that id.
                $media->path = $media->getPathRelativeToRoot();
                $media->save();

                $image = $media;
            });
        } catch (Throwable $exception) {
            $this->discard($image);

            throw $exception;
        }

        /** @var ProductImage $image */
        return $image;
    }

    /**
     * Remove the file that was left behind when the store failed.
     *
     * The row is already gone: the transaction rolled it back. The file is
     * not, so it is removed here.
     */
    private function discard(?ProductImage $image): void
    {
        if ($image === null) {
            return;
        }

        $image->delete();
    }

    /**
     * Say that the other images of the product are not the main one.
     */
    private function demoteTheOthers(ProductImage $image): void
    {
        if ($image->product_id === null) {
            return;
        }

        ProductImage::query()
            ->where('product_id', $image->product_id)
            ->where('id', '!=', $image->getKey())
            ->update(['is_primary' => false]);
    }

    /**
     * Write the thumbnail of the product again.
     *
     * The column holds the path of the smallest thumbnail of the main image,
     * so the catalog can show it without building anything.
     */
    private function refreshTheThumbnailOf(Product $product): void
    {
        $main = $product->images()->where('is_primary', true)->first();

        $product->thumbnail_path = $this->thumbnailPathOf($main);
        $product->save();
    }

    /**
     * The path of the thumbnail of an image.
     */
    private function thumbnailPathOf(?ProductImage $image): ?string
    {
        if ($image === null) {
            return null;
        }

        $thumbnail = Media::firstThumbnailName();

        if ($thumbnail !== null && $image->hasGeneratedConversion($thumbnail)) {
            return $image->getPathRelativeToRoot($thumbnail);
        }

        return $image->getPathRelativeToRoot();
    }

    /**
     * Check that the file is one the store accepts.
     *
     * The check happens before anything is written, so a file that does not
     * pass leaves no trace.
     */
    private function guard(UploadedFile $file): void
    {
        $maxSize = Media::maxSize();
        $size = $file->getSize();

        if ($size !== false && $size > $maxSize * 1024) {
            throw InvalidMediaFile::tooBig($maxSize);
        }

        $validator = Validator::make(['file' => $file], [
            'file' => [File::types(Media::allowedMimes())],
        ]);

        if ($validator->fails()) {
            throw InvalidMediaFile::withWrongType();
        }
    }
}
