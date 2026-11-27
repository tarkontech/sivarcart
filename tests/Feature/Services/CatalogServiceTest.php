<?php

declare(strict_types=1);

use Tarkontech\SivarCart\Data\CatalogFilters;
use Tarkontech\SivarCart\Enums\CatalogSort;
use Tarkontech\SivarCart\Enums\ProductStatus;
use Tarkontech\SivarCart\Models\Brand;
use Tarkontech\SivarCart\Models\Category;
use Tarkontech\SivarCart\Models\Product;
use Tarkontech\SivarCart\Models\ProductVariant;
use Tarkontech\SivarCart\Services\CatalogService;

/**
 * The ids of the products of a page of the catalog.
 *
 * @return list<int>
 */
function catalogIds(CatalogFilters $filters): array
{
    return collect(app(CatalogService::class)->search($filters)->items())
        ->pluck('id')
        ->map(fn (mixed $id): int => (int) $id)
        ->all();
}

it('devuelve solo los productos publicados', function () {
    $published = Product::factory()->create();
    $draft = Product::factory()->draft()->create();
    $archived = Product::factory()->create(['status' => ProductStatus::ARCHIVED]);

    $ids = catalogIds(new CatalogFilters);

    expect($ids)->toContain($published->getKey())
        ->and($ids)->not->toContain($draft->getKey())
        ->and($ids)->not->toContain($archived->getKey());
});

it('exige una variante activa para vender el producto', function () {
    $product = Product::factory()->variable()->create();

    ProductVariant::factory()->create([
        'product_id' => $product->getKey(),
        'is_active' => false,
    ]);

    expect(catalogIds(new CatalogFilters))->not->toContain($product->getKey());
});

it('filtra por categoría', function () {
    $category = Category::factory()->create();

    $inside = Product::factory()->create();
    $inside->categories()->attach($category);

    $outside = Product::factory()->create();

    $ids = catalogIds(new CatalogFilters(categoryId: $category->getKey()));

    expect($ids)->toContain($inside->getKey())
        ->and($ids)->not->toContain($outside->getKey());
});

it('incluye los productos de las subcategorías', function () {
    $root = Category::factory()->create();
    $child = Category::factory()->childOf($root)->create();
    $grandchild = Category::factory()->childOf($child)->create();

    $inChild = Product::factory()->create();
    $inChild->categories()->attach($child);

    $inGrandchild = Product::factory()->create();
    $inGrandchild->categories()->attach($grandchild);

    $outside = Product::factory()->create();

    $ids = catalogIds(new CatalogFilters(categoryId: $root->getKey()));

    expect($ids)->toContain($inChild->getKey())
        ->and($ids)->toContain($inGrandchild->getKey())
        ->and($ids)->not->toContain($outside->getKey());
});

it('filtra por marca', function () {
    $brand = Brand::factory()->create();

    $inside = Product::factory()->create(['brand_id' => $brand->getKey()]);
    $outside = Product::factory()->create();

    $ids = catalogIds(new CatalogFilters(brandId: $brand->getKey()));

    expect($ids)->toContain($inside->getKey())
        ->and($ids)->not->toContain($outside->getKey());
});

it('filtra por el precio mínimo', function () {
    $cheap = ProductVariant::factory()->create(['price' => 10]);
    $expensive = ProductVariant::factory()->create(['price' => 80]);

    $ids = catalogIds(new CatalogFilters(minPrice: 20.0));

    expect($ids)->toContain($expensive->product_id)
        ->and($ids)->not->toContain($cheap->product_id);
});

it('filtra por el precio máximo', function () {
    $cheap = ProductVariant::factory()->create(['price' => 10]);
    $expensive = ProductVariant::factory()->create(['price' => 80]);

    $ids = catalogIds(new CatalogFilters(maxPrice: 20.0));

    expect($ids)->toContain($cheap->product_id)
        ->and($ids)->not->toContain($expensive->product_id);
});

it('filtra por un rango de precio', function () {
    $cheap = ProductVariant::factory()->create(['price' => 10]);
    $middle = ProductVariant::factory()->create(['price' => 25]);
    $expensive = ProductVariant::factory()->create(['price' => 80]);

    $ids = catalogIds(new CatalogFilters(minPrice: 20.0, maxPrice: 30.0));

    expect($ids)->toContain($middle->product_id)
        ->and($ids)->not->toContain($cheap->product_id)
        ->and($ids)->not->toContain($expensive->product_id);
});

it('filtra por disponibilidad', function () {
    $empty = ProductVariant::factory()->outOfStock()->create();
    $reserved = ProductVariant::factory()->create([
        'stock_quantity' => 5,
        'reserved_quantity' => 5,
    ]);
    $available = ProductVariant::factory()->create([
        'stock_quantity' => 5,
        'reserved_quantity' => 1,
    ]);

    $mixed = Product::factory()->variable()->create();

    ProductVariant::factory()->create([
        'product_id' => $mixed->getKey(),
        'stock_quantity' => 0,
    ]);
    ProductVariant::factory()->create([
        'product_id' => $mixed->getKey(),
        'stock_quantity' => 3,
    ]);

    $ids = catalogIds(new CatalogFilters(inStockOnly: true));

    expect($ids)->toContain($available->product_id)
        ->and($ids)->toContain($mixed->getKey())
        ->and($ids)->not->toContain($empty->product_id)
        ->and($ids)->not->toContain($reserved->product_id);
});

it('busca por el nombre del producto', function () {
    $match = Product::factory()->create(['name' => 'Zapatilla urbana']);
    $other = Product::factory()->create(['name' => 'Mochila de lona']);

    $ids = catalogIds(new CatalogFilters(search: 'zapatilla'));

    expect($ids)->toContain($match->getKey())
        ->and($ids)->not->toContain($other->getKey());
});

it('busca por el sku de la variante', function () {
    $match = ProductVariant::factory()->create(['sku' => 'ZAP-001']);
    $other = ProductVariant::factory()->create(['sku' => 'MOC-002']);

    $ids = catalogIds(new CatalogFilters(search: 'ZAP-001'));

    expect($ids)->toContain($match->product_id)
        ->and($ids)->not->toContain($other->product_id);
});

it('combina varios filtros', function () {
    $brand = Brand::factory()->create();
    $category = Category::factory()->create();

    $match = Product::factory()->create(['brand_id' => $brand->getKey()]);
    $match->categories()->attach($category);

    ProductVariant::factory()->create([
        'product_id' => $match->getKey(),
        'price' => 30,
        'stock_quantity' => 10,
    ]);

    $wrongBrand = Product::factory()->create();
    ProductVariant::factory()->create([
        'product_id' => $wrongBrand->getKey(),
        'price' => 30,
        'stock_quantity' => 10,
    ]);

    $wrongPrice = Product::factory()->create(['brand_id' => $brand->getKey()]);
    ProductVariant::factory()->create([
        'product_id' => $wrongPrice->getKey(),
        'price' => 300,
        'stock_quantity' => 10,
    ]);

    $wrongStock = Product::factory()->create(['brand_id' => $brand->getKey()]);
    ProductVariant::factory()->create([
        'product_id' => $wrongStock->getKey(),
        'price' => 30,
        'stock_quantity' => 0,
    ]);

    $ids = catalogIds(new CatalogFilters(
        categoryId: $category->getKey(),
        brandId: $brand->getKey(),
        minPrice: 20.0,
        maxPrice: 40.0,
        inStockOnly: true,
    ));

    expect($ids)->toBe([$match->getKey()]);
});

it('ordena por precio', function () {
    $cheap = ProductVariant::factory()->create(['price' => 10]);
    $middle = ProductVariant::factory()->create(['price' => 25]);
    $expensive = ProductVariant::factory()->create(['price' => 80]);

    $ascending = catalogIds(new CatalogFilters(sort: CatalogSort::PRICE_ASC));
    $descending = catalogIds(new CatalogFilters(sort: CatalogSort::PRICE_DESC));

    expect($ascending)->toBe([$cheap->product_id, $middle->product_id, $expensive->product_id])
        ->and($descending)->toBe([$expensive->product_id, $middle->product_id, $cheap->product_id]);
});

it('ordena por el precio de la variante más barata', function () {
    $product = Product::factory()->variable()->create();

    ProductVariant::factory()->create([
        'product_id' => $product->getKey(),
        'price' => 90,
    ]);
    ProductVariant::factory()->create([
        'product_id' => $product->getKey(),
        'price' => 15,
    ]);

    $other = ProductVariant::factory()->create(['price' => 40]);

    $ids = catalogIds(new CatalogFilters(sort: CatalogSort::PRICE_ASC));

    expect($ids)->toBe([$product->getKey(), $other->product_id]);
});

it('ordena por nombre', function () {
    $gamma = Product::factory()->create(['name' => 'gamma']);
    $alpha = Product::factory()->create(['name' => 'alpha']);
    $beta = Product::factory()->create(['name' => 'beta']);

    $ids = catalogIds(new CatalogFilters(sort: CatalogSort::NAME));

    expect($ids)->toBe([$alpha->getKey(), $beta->getKey(), $gamma->getKey()]);
});

it('ordena por los más recientes', function () {
    $older = Product::factory()->create(['published_at' => now()->subDays(3)]);
    $newer = Product::factory()->create(['published_at' => now()->subDay()]);

    $ids = catalogIds(new CatalogFilters(sort: CatalogSort::RECENT));

    expect($ids)->toBe([$newer->getKey(), $older->getKey()]);
});

it('pagina los resultados', function () {
    $products = Product::factory()->count(3)->create();

    $first = app(CatalogService::class)->search(new CatalogFilters(perPage: 2, page: 1));
    $second = app(CatalogService::class)->search(new CatalogFilters(perPage: 2, page: 2));

    expect($first->total())->toBe(3)
        ->and($first->items())->toHaveCount(2)
        ->and($first->lastPage())->toBe(2)
        ->and($second->items())->toHaveCount(1)
        ->and($second->items()[0]->getKey())->toBe($products->first()->getKey());
});

it('acota el tamaño de página', function () {
    Product::factory()->count(2)->create();

    $paginator = app(CatalogService::class)->search(new CatalogFilters(perPage: 500));

    expect($paginator->perPage())->toBe(100);
});

it('no encuentra nada con una categoría que no existe', function () {
    Product::factory()->create();

    expect(catalogIds(new CatalogFilters(categoryId: 99999)))->toBe([]);
});
