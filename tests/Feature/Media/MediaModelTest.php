<?php

declare(strict_types=1);

use SivarcartCore\SivarcartCore\Models\Brand;
use SivarcartCore\SivarcartCore\Models\Product;
use SivarcartCore\SivarcartCore\Models\ProductImage;
use SivarcartCore\SivarcartCore\Models\ProductVariant;
use SivarcartCore\SivarcartCore\Models\Store;
use Spatie\MediaLibrary\Conversions\Conversion;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

it('stores the media in the table of the images of the package', function (): void {
    expect(config('media-library.media_model'))->toBe(ProductImage::class)
        ->and(is_subclass_of(ProductImage::class, Media::class))->toBeTrue()
        ->and((new ProductImage)->getTable())->toBe('sivarcart_product_images');
});

it('gives the media the model of the package', function (): void {
    expect(Product::factory()->create()->getMediaModel())->toBe(ProductImage::class)
        ->and(Brand::factory()->create()->getMediaModel())->toBe(ProductImage::class)
        ->and(Store::factory()->create()->getMediaModel())->toBe(ProductImage::class);
});

it('numbers the media with the column of the domain', function (): void {
    $image = new ProductImage;

    $column = (fn (): string => $this->determineOrderColumnName())->call($image);

    expect($column)->toBe('position');
});

it('keeps the factory of the images', function (): void {
    $image = ProductImage::factory()->create();

    expect($image)->toBeInstanceOf(ProductImage::class)
        ->and($image->product_id)->not->toBeNull();
});

it('lets the products, the variants, the brands and the store keep media', function (): void {
    $models = [Product::class, ProductVariant::class, Brand::class, Store::class];

    foreach ($models as $model) {
        expect(class_implements($model))->toContain(HasMedia::class);
    }
});

it('declares the collection of the images of the products', function (): void {
    $collections = Product::factory()->create()->getRegisteredMediaCollections();

    expect($collections->pluck('name')->all())->toContain('images')
        ->and($collections->firstWhere('name', 'images')->singleFile)->toBeFalse();
});

it('declares a single logo for the brands and for the store', function (): void {
    $brand = Brand::factory()->create()->getRegisteredMediaCollections();
    $store = Store::factory()->create()->getRegisteredMediaCollections();

    expect($brand->pluck('name')->all())->toContain('logo')
        ->and($brand->firstWhere('name', 'logo')->singleFile)->toBeTrue()
        ->and($store->pluck('name')->all())->toContain('logo', 'favicon')
        ->and($store->firstWhere('name', 'favicon')->singleFile)->toBeTrue();
});

it('builds the thumbnails that the configuration asks for', function (): void {
    $product = Product::factory()->create();

    $product->registerMediaConversions();

    $names = array_map(
        fn (Conversion $conversion): string => $conversion->getName(),
        $product->mediaConversions,
    );

    expect($names)->toBe(array_keys(config('sivarcart-core.media.thumbnails', [])));
});

it('writes the manipulations of the thumbnail so they survive the database', function (): void {
    $product = Product::factory()->create();

    $product->registerMediaConversions();

    $manipulations = $product->mediaConversions[0]->getManipulations()->toArray();

    $decoded = json_decode((string) json_encode($manipulations), true);

    // The name of the adjustment is what lets the media library turn the text
    // back into the enum it needs when it reads the media from the database.
    expect($decoded['fit'])->toHaveKey('fit')
        ->and($decoded['fit']['fit'])->toBe('crop');
});

it('does not publish the migration of the media library', function (): void {
    $files = glob(database_path('migrations').'/*_create_media_table.php');

    expect($files)->toBeEmpty();
});
