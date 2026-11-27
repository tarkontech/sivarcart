<?php

declare(strict_types=1);

use Inertia\Testing\AssertableInertia as Assert;
use SivarcartCore\SivarcartCore\Models\Brand;
use SivarcartCore\SivarcartCore\Models\Category;
use SivarcartCore\SivarcartCore\Models\Product;
use SivarcartCore\SivarcartCore\Models\Store;

it('shows the home of the store', function () {
    Store::factory()->create();

    $this->get('/tienda')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Storefront/Home')
            ->has('products.data')
            ->has('featured'),
        );
});

it('shows the products of the catalog', function () {
    Store::factory()->create();

    $product = Product::factory()->create(['name' => 'Café de El Salvador']);

    $this->get('/tienda/productos')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Storefront/Catalog/Index')
            ->has('products.data', 1)
            ->where('products.data.0.name', 'Café de El Salvador')
            ->where('products.data.0.uuid', $product->uuid)
            ->has('filters')
            ->has('sorts'),
        );
});

it('does not show the products that are not published', function () {
    Store::factory()->create();

    Product::factory()->create(['status' => 'draft']);

    $this->get('/tienda/productos')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->has('products.data', 0));
});

it('filters the catalog by the text of la búsqueda', function () {
    Store::factory()->create();

    Product::factory()->create(['name' => 'Café de El Salvador']);
    Product::factory()->create(['name' => 'Pan de San Miguel']);

    $this->get('/tienda/productos?search=Café')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->has('products.data', 1));
});

it('filters the catalog by the categoría', function () {
    Store::factory()->create();

    $category = Category::factory()->create(['name' => 'Bebidas']);

    $product = Product::factory()->create();
    $product->categories()->attach($category->getKey());

    Product::factory()->create();

    $this->get('/tienda/productos?category_id='.$category->getKey())
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->has('products.data', 1));
});

it('filters the catalog by la marca', function () {
    Store::factory()->create();

    $brand = Brand::factory()->create();

    Product::factory()->create(['brand_id' => $brand->getKey()]);

    Product::factory()->create();

    $this->get('/tienda/productos?brand_id='.$brand->getKey())
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->has('products.data', 1));
});

it('shows the page of a product', function () {
    Store::factory()->create();

    $product = Product::factory()->create(['name' => 'Café de El Salvador']);

    $this->get('/tienda/productos/'.$product->uuid)
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Storefront/Products/Show')
            ->where('product.name', 'Café de El Salvador')
            ->where('product.uuid', $product->uuid)
            ->has('product.variants'),
        );
});

it('answers 404 for a product that does not exist', function () {
    Store::factory()->create();

    $this->get('/tienda/productos/no-existe')->assertNotFound();
});

it('shows the categorías of la tienda en el header', function () {
    Store::factory()->create();

    Category::factory()->create(['name' => 'Bebidas']);

    $this->get('/tienda')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('categories', 1)
            ->where('categories.0.name', 'Bebidas'),
        );
});
