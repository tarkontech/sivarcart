<?php

declare(strict_types=1);

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use SivarcartCore\SivarcartCore\Models\Brand;
use SivarcartCore\SivarcartCore\Models\Category;
use SivarcartCore\SivarcartCore\Models\Product;
use SivarcartCore\SivarcartCore\Models\ProductVariant;
use SivarcartCore\SivarcartCore\Services\MediaService;

it('lists the products of the catalog', function () {
    $product = Product::factory()->create(['name' => 'ZapatillaRunner']);

    $this->getJson('/api/sivarcart/products')
        ->assertOk()
        ->assertJsonPath('data.0.id', $product->uuid)
        ->assertJsonPath('data.0.name', 'ZapatillaRunner')
        ->assertJsonPath('data.0.sku', $product->defaultVariant()->sku);
});

it('does not list the products that are not published', function () {
    Product::factory()->draft()->create();

    $this->getJson('/api/sivarcart/products')
        ->assertOk()
        ->assertJsonCount(0, 'data');
});

it('filters the products by category, including the subcategories', function () {
    $parent = Category::factory()->create();
    $child = Category::factory()->childOf($parent)->create();

    $inside = Product::factory()->create();
    $inside->categories()->attach($child);

    $outside = Product::factory()->create();

    $this->getJson('/api/sivarcart/products?category_id='.$parent->getKey())
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $inside->uuid);

    expect($outside->uuid)->not->toBe($inside->uuid);
});

it('filters the products by brand', function () {
    $brand = Brand::factory()->create();

    $inside = Product::factory()->create(['brand_id' => $brand->getKey()]);
    Product::factory()->create();

    $this->getJson('/api/sivarcart/products?brand_id='.$brand->getKey())
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $inside->uuid);
});

it('filters the products by price', function () {
    $cheap = Product::factory()->create();
    $cheap->defaultVariant()->update(['price' => 10.00]);

    $expensive = Product::factory()->create();
    $expensive->defaultVariant()->update(['price' => 300.00]);

    $this->getJson('/api/sivarcart/products?max_price=50')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $cheap->uuid);

    $this->getJson('/api/sivarcart/products?min_price=100')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $expensive->uuid);
});

it('filters the products that have units available', function () {
    $available = Product::factory()->create();
    $available->defaultVariant()->update(['stock_quantity' => 5, 'reserved_quantity' => 0]);

    $empty = Product::factory()->create();
    $empty->defaultVariant()->update(['stock_quantity' => 0]);

    $this->getJson('/api/sivarcart/products?in_stock=1')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $available->uuid);
});

it('searches the products by name and by sku', function () {
    $found = Product::factory()->create(['name' => 'Zapatillaurbana']);
    Product::factory()->create(['name' => 'Camiseta']);

    $this->getJson('/api/sivarcart/products?search=zapatilla')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $found->uuid);
});

it('sorts the products by price', function () {
    $cheap = Product::factory()->create();
    $cheap->defaultVariant()->update(['price' => 10.00]);

    $expensive = Product::factory()->create();
    $expensive->defaultVariant()->update(['price' => 300.00]);

    $this->getJson('/api/sivarcart/products?sort=price_asc')
        ->assertOk()
        ->assertJsonPath('data.0.id', $cheap->uuid)
        ->assertJsonPath('data.1.id', $expensive->uuid);
});

it('refuses a filter that does not make sense', function () {
    $this->getJson('/api/sivarcart/products?sort=por-precios')
        ->assertStatus(422)
        ->assertJsonValidationErrors(['sort']);
});

it('shows a product with its variants', function () {
    $product = Product::factory()->create();
    $variant = $product->defaultVariant();

    $this->getJson('/api/sivarcart/products/'.$product->uuid)
        ->assertOk()
        ->assertJsonPath('data.id', $product->uuid)
        ->assertJsonPath('data.variants.0.id', $variant->uuid)
        ->assertJsonPath('data.variants.0.price', 25.5)
        ->assertJsonPath('data.variants.0.available_stock', 25);
});

it('does not show a product that is not published', function () {
    $product = Product::factory()->draft()->create();

    $this->getJson('/api/sivarcart/products/'.$product->uuid)->assertNotFound();
});

it('shows the images of a product', function (): void {
    Storage::fake('public');

    $product = Product::factory()->create();

    $image = app(MediaService::class)->storeProductImage(
        $product,
        UploadedFile::fake()->image('foto.jpg', 400, 300),
        'Una foto del producto',
    );

    $this->getJson('/api/sivarcart/products/'.$product->uuid)
        ->assertOk()
        ->assertJsonPath('data.images.0.path', $image->path)
        ->assertJsonPath('data.images.0.disk', 'public')
        ->assertJsonPath('data.images.0.alt', 'Una foto del producto')
        ->assertJsonPath('data.images.0.position', 1)
        ->assertJsonPath('data.images.0.is_primary', false)
        ->assertJsonPath('data.images.0.url', $image->getUrl())
        ->assertJsonPath('data.images.0.thumbnail_url', $image->getUrl('thumb'));
});
it('answers with a 404 for a product that does not exist', function () {
    $this->getJson('/api/sivarcart/products/00000000-0000-0000-0000-000000000000')
        ->assertNotFound();
});

it('lists the categories and the brands of the store', function () {
    $category = Category::factory()->create(['name' => 'Zapatos']);
    $brand = Brand::factory()->create(['name' => 'Sivar']);

    $this->getJson('/api/sivarcart/categories')
        ->assertOk()
        ->assertJsonPath('data.0.name', 'Zapatos');

    $this->getJson('/api/sivarcart/brands')
        ->assertOk()
        ->assertJsonPath('data.0.name', 'Sivar');
});

it('does not list the categories that are not active', function () {
    Category::factory()->create(['is_active' => false]);

    $this->getJson('/api/sivarcart/categories')
        ->assertOk()
        ->assertJsonCount(0, 'data');
});

it('answers with a variant that carries its price and its stock', function () {
    $variant = ProductVariant::factory()->create(['price' => 12.75, 'stock_quantity' => 3]);

    $this->getJson('/api/sivarcart/products')
        ->assertOk()
        ->assertJsonPath('data.0.variants.0.price', 12.75)
        ->assertJsonPath('data.0.variants.0.stock_quantity', 3);
});
