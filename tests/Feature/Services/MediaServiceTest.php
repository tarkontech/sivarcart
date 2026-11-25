<?php

declare(strict_types=1);

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use SivarcartCore\SivarcartCore\Exceptions\InvalidMediaFile;
use SivarcartCore\SivarcartCore\Models\Brand;
use SivarcartCore\SivarcartCore\Models\Product;
use SivarcartCore\SivarcartCore\Models\ProductImage;
use SivarcartCore\SivarcartCore\Models\ProductVariant;
use SivarcartCore\SivarcartCore\Models\Store;
use SivarcartCore\SivarcartCore\Services\MediaService;

beforeEach(function (): void {
    Storage::fake('public');
});

it('stores the image of a product', function (): void {
    $product = Product::factory()->create();

    $image = app(MediaService::class)->storeProductImage(
        $product,
        UploadedFile::fake()->image('foto.jpg', 400, 300),
        'Una foto del producto',
    );

    expect($image)->toBeInstanceOf(ProductImage::class)
        ->and($image->product_id)->toBe($product->getKey())
        ->and($image->product_variant_id)->toBeNull()
        ->and($image->alt)->toBe('Una foto del producto')
        ->and($image->is_primary)->toBeFalse()
        ->and($image->disk)->toBe('public')
        ->and($image->position)->toBe(1)
        ->and($image->model_type)->toBe(Product::class)
        ->and($image->model_id)->toBe($product->getKey())
        ->and($image->uuid)->not->toBeEmpty()
        ->and($image->path)->not->toBeEmpty();

    Storage::disk('public')->assertExists($image->path);
});

it('builds the thumbnail of the image', function (): void {
    $product = Product::factory()->create();

    $image = app(MediaService::class)->storeProductImage(
        $product,
        UploadedFile::fake()->image('foto.jpg', 400, 300),
    );

    expect($image->hasGeneratedConversion('thumb'))->toBeTrue();

    Storage::disk('public')->assertExists($image->getPathRelativeToRoot('thumb'));
});

it('reads the thumbnail back from the database', function (): void {
    $product = Product::factory()->create();

    $image = app(MediaService::class)->storeProductImage(
        $product,
        UploadedFile::fake()->image('foto.jpg', 400, 300),
    );

    $read = ProductImage::query()->findOrFail($image->getKey());

    expect($read->hasGeneratedConversion('thumb'))->toBeTrue();

    Storage::disk('public')->assertExists($read->getPathRelativeToRoot('thumb'));
});

it('stores the image of a variant in the gallery of the product', function (): void {
    $product = Product::factory()->create();
    $variant = ProductVariant::factory()->for($product)->create();

    $image = app(MediaService::class)->storeVariantImage(
        $variant,
        UploadedFile::fake()->image('variante.jpg', 400, 300),
    );

    expect($image->product_id)->toBe($product->getKey())
        ->and($image->product_variant_id)->toBe($variant->getKey())
        ->and($image->model_type)->toBe(ProductVariant::class)
        ->and($product->images()->count())->toBe(1);
});

it('numbers the images of the gallery in order', function (): void {
    $product = Product::factory()->create();
    $variant = ProductVariant::factory()->for($product)->create();

    $service = app(MediaService::class);

    $first = $service->storeProductImage($product, UploadedFile::fake()->image('uno.jpg'));
    $second = $service->storeVariantImage($variant, UploadedFile::fake()->image('dos.jpg'));

    expect($first->position)->toBe(1)
        ->and($second->position)->toBe(2);
});

it('refuses a file that is not an image', function (): void {
    $product = Product::factory()->create();

    $file = UploadedFile::fake()->create('documento.pdf', 10, 'application/pdf');

    expect(fn (): ProductImage => app(MediaService::class)->storeProductImage($product, $file))
        ->toThrow(InvalidMediaFile::class);
});

it('refuses a file that is too big', function (): void {
    $product = Product::factory()->create();

    $file = UploadedFile::fake()->create('grande.jpg', 20000, 'image/jpeg');

    expect(fn (): ProductImage => app(MediaService::class)->storeProductImage($product, $file))
        ->toThrow(InvalidMediaFile::class);
});

it('leaves nothing behind when the file is refused', function (): void {
    $product = Product::factory()->create();

    $file = UploadedFile::fake()->create('documento.pdf', 10, 'application/pdf');

    expect(fn (): ProductImage => app(MediaService::class)->storeProductImage($product, $file))
        ->toThrow(InvalidMediaFile::class);

    expect(ProductImage::query()->count())->toBe(0)
        ->and(Storage::disk('public')->allFiles())->toBe([]);
});

it('stores the logo of a brand', function (): void {
    $brand = Brand::factory()->create();

    $image = app(MediaService::class)->storeBrandLogo(
        $brand,
        UploadedFile::fake()->image('logo.jpg', 200, 200),
    );

    expect($brand->fresh()->logo_path)->toBe($image->getPathRelativeToRoot());

    Storage::disk('public')->assertExists($image->path);
});

it('replaces the logo of a brand that already had one', function (): void {
    $brand = Brand::factory()->create();

    $service = app(MediaService::class);

    $old = $service->storeBrandLogo($brand, UploadedFile::fake()->image('viejo.jpg'));
    $new = $service->storeBrandLogo($brand, UploadedFile::fake()->image('nuevo.jpg'));

    expect($brand->fresh()->logo_path)->toBe($new->getPathRelativeToRoot())
        ->and(ProductImage::query()->count())->toBe(1);

    Storage::disk('public')->assertMissing($old->path);
});

it('stores the logo and the favicon of the store', function (): void {
    $store = Store::factory()->create();

    $service = app(MediaService::class);

    $logo = $service->storeStoreLogo($store, UploadedFile::fake()->image('logo.png', 200, 200));
    $favicon = $service->storeStoreFavicon($store, UploadedFile::fake()->image('favicon.png', 64, 64));

    expect($store->fresh()->logo_path)->toBe($logo->getPathRelativeToRoot())
        ->and($store->fresh()->favicon_path)->toBe($favicon->getPathRelativeToRoot());

    Storage::disk('public')->assertExists($logo->path);
    Storage::disk('public')->assertExists($favicon->path);
});

it('leaves one main image in the gallery of the product', function (): void {
    $product = Product::factory()->create();

    $service = app(MediaService::class);

    $first = $service->storeProductImage($product, UploadedFile::fake()->image('uno.jpg'), null, true);
    $second = $service->storeProductImage($product, UploadedFile::fake()->image('dos.jpg'));

    expect($first->fresh()->is_primary)->toBeTrue()
        ->and($second->fresh()->is_primary)->toBeFalse();

    $service->makePrimary($second);

    expect($second->fresh()->is_primary)->toBeTrue()
        ->and($first->fresh()->is_primary)->toBeFalse()
        ->and($product->images()->where('is_primary', true)->count())->toBe(1);
});

it('writes the thumbnail of the main image in the product', function (): void {
    $product = Product::factory()->create();

    $image = app(MediaService::class)->storeProductImage(
        $product,
        UploadedFile::fake()->image('foto.jpg', 400, 300),
        null,
        true,
    );

    expect($product->fresh()->thumbnail_path)->toBe($image->getPathRelativeToRoot('thumb'));
});

it('removes the image and its file', function (): void {
    $product = Product::factory()->create();

    $image = app(MediaService::class)->storeProductImage(
        $product,
        UploadedFile::fake()->image('foto.jpg', 400, 300),
    );

    $path = $image->path;

    app(MediaService::class)->delete($image);

    expect(ProductImage::query()->count())->toBe(0);

    Storage::disk('public')->assertMissing($path);
});
