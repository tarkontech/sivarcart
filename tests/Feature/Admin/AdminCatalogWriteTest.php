<?php

declare(strict_types=1);

use Inertia\Testing\AssertableInertia as Assert;
use SivarcartCore\SivarcartCore\Enums\ProductStatus;
use SivarcartCore\SivarcartCore\Enums\ProductType;
use SivarcartCore\SivarcartCore\Models\Brand;
use SivarcartCore\SivarcartCore\Models\Category;
use SivarcartCore\SivarcartCore\Models\PaymentMethod;
use SivarcartCore\SivarcartCore\Models\Product;
use SivarcartCore\SivarcartCore\Models\ProductVariant;
use SivarcartCore\SivarcartCore\Models\ShippingMethod;
use SivarcartCore\SivarcartCore\Models\StoreRole;
use Workbench\App\Models\User;

/**
 * Sign a member of the staff of the store in.
 *
 * @param  list<string>  $permissions
 */
function staffOfCatalog(array $permissions = ['*']): User
{
    $user = User::factory()->create();

    $user->assignStoreRole(StoreRole::factory()->create(['permissions' => $permissions]));

    test()->actingAs($user);

    return $user;
}

it('shows the form of a new product', function () {
    staffOfCatalog();

    $this->get('/sivarcart/admin/products/create')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Products/Form')
            ->where('product.uuid', null)
            ->where('product.status', 'draft')
            ->has('brands')
            ->has('categories')
            ->has('statuses', 3)
            ->has('types', 2),
        );
});

it('creates a product of the catalog', function () {
    staffOfCatalog();

    $this->post('/sivarcart/admin/products', [
        'name' => 'Camiseta azul',
        'status' => 'draft',
        'type' => 'simple',
        'variants' => [
            ['name' => 'Única', 'sku' => 'CAM-AZUL', 'price' => '25.50', 'stock_quantity' => 10],
        ],
    ])->assertRedirect();

    $product = Product::query()->where('name', 'Camiseta azul')->firstOrFail();

    expect($product->slug)->toBe('camiseta-azul')
        ->and($product->variants)->toHaveCount(1)
        ->and((float) $product->variants->first()->price)->toBe(25.5)
        ->and($product->variants->first()->stock_quantity)->toBe(10)
        ->and($product->variants->first()->is_default)->toBeTrue();
});

it('publishes a product and marks the day it was published', function () {
    staffOfCatalog();

    $this->post('/sivarcart/admin/products', [
        'name' => 'Camiseta azul',
        'status' => 'published',
        'type' => 'simple',
        'variants' => [['price' => '10', 'stock_quantity' => 2]],
    ])->assertRedirect();

    $product = Product::query()->where('name', 'Camiseta azul')->firstOrFail();

    expect($product->status)->toBe(ProductStatus::PUBLISHED)
        ->and($product->published_at)->not->toBeNull();
});

it('files a product under the categories the staff chose', function () {
    staffOfCatalog();

    $category = Category::factory()->create();

    $this->post('/sivarcart/admin/products', [
        'name' => 'Camiseta azul',
        'status' => 'draft',
        'type' => 'simple',
        'categories' => [(string) $category->getKey()],
        'variants' => [['price' => '10', 'stock_quantity' => 2]],
    ])->assertRedirect();

    $product = Product::query()->where('name', 'Camiseta azul')->firstOrFail();

    expect($product->categories)->toHaveCount(1)
        ->and($product->categories->first()->getKey())->toBe($category->getKey());
});

it('gives a product the brand the staff chose', function () {
    staffOfCatalog();

    $brand = Brand::factory()->create();

    $this->post('/sivarcart/admin/products', [
        'name' => 'Camiseta azul',
        'status' => 'draft',
        'type' => 'simple',
        'brand_id' => (string) $brand->getKey(),
        'variants' => [['price' => '10', 'stock_quantity' => 2]],
    ])->assertRedirect();

    $product = Product::query()->where('name', 'Camiseta azul')->firstOrFail();

    expect($product->brand_id)->toBe($brand->getKey());
});

it('writes the product the staff changed', function () {
    staffOfCatalog();

    $product = Product::factory()->create(['name' => 'Camiseta azul']);

    $this->put('/sivarcart/admin/products/'.$product->uuid, [
        'name' => 'Camiseta verde',
        'status' => 'published',
        'type' => 'simple',
        'variants' => [[
            'uuid' => $product->variants->first()->uuid,
            'price' => '30',
            'stock_quantity' => 4,
        ]],
    ])->assertRedirect();

    $product->refresh();

    expect($product->name)->toBe('Camiseta verde')
        ->and((float) $product->variants->first()->price)->toBe(30.0)
        ->and($product->variants->first()->stock_quantity)->toBe(4);
});

it('removes the variants the staff took away from the form', function () {
    staffOfCatalog();

    $product = Product::factory()->variable()->create();

    $variant = ProductVariant::factory()->create(['product_id' => $product->getKey()]);

    $this->put('/sivarcart/admin/products/'.$product->uuid, [
        'name' => $product->name,
        'status' => 'draft',
        'type' => 'variable',
        'variants' => [],
    ])->assertRedirect();

    expect($product->variants()->count())->toBe(0)
        ->and(ProductVariant::query()->whereKey($variant->getKey())->withTrashed()->exists())->toBeTrue();
});

it('takes a product off the catalog', function () {
    staffOfCatalog();

    $product = Product::factory()->create();

    $this->delete('/sivarcart/admin/products/'.$product->uuid)->assertRedirect();

    expect(Product::query()->whereKey($product->getKey())->exists())->toBeFalse()
        ->and(Product::query()->withTrashed()->whereKey($product->getKey())->exists())->toBeTrue();
});

it('does not write the catalog to the staff that can only read it', function () {
    staffOfCatalog(['catalog.view']);

    $this->post('/sivarcart/admin/products', [
        'name' => 'Camiseta azul',
        'status' => 'draft',
        'type' => 'simple',
        'variants' => [['price' => '10', 'stock_quantity' => 2]],
    ])->assertForbidden();

    expect(Product::query()->where('name', 'Camiseta azul')->exists())->toBeFalse();
});

it('does not show the button to write the catalog to the staff that can only read it', function () {
    staffOfCatalog(['catalog.view']);

    $this->get('/sivarcart/admin/products')
        ->assertInertia(fn (Assert $page) => $page->where('canUpdate', false));
});

it('writes a category of the catalog', function () {
    staffOfCatalog();

    $this->post('/sivarcart/admin/categories', [
        'name' => 'Ropa',
        'description' => 'La ropa de la tienda.',
        'is_active' => true,
    ])->assertRedirect();

    $category = Category::query()->where('name', 'Ropa')->firstOrFail();

    expect($category->slug)->toBe('ropa')
        ->and($category->description)->toBe('La ropa de la tienda.');
});

it('files a category under another one', function () {
    staffOfCatalog();

    $parent = Category::factory()->create();

    $this->post('/sivarcart/admin/categories', [
        'name' => 'Camisetas',
        'parent_id' => (string) $parent->getKey(),
        'is_active' => true,
    ])->assertRedirect();

    expect(Category::query()->where('name', 'Camisetas')->firstOrFail()->parent_id)
        ->toBe($parent->getKey());
});

it('refuses to file a category under itself', function () {
    staffOfCatalog();

    $parent = Category::factory()->create();
    $child = Category::factory()->childOf($parent)->create();

    $this->put('/sivarcart/admin/categories/'.$parent->uuid, [
        'name' => $parent->name,
        'parent_id' => (string) $child->getKey(),
        'is_active' => true,
    ])->assertRedirect();

    expect($parent->fresh()->parent_id)->toBeNull();
});

it('writes a brand of the catalog', function () {
    staffOfCatalog();

    $this->post('/sivarcart/admin/brands', [
        'name' => 'Acme',
        'description' => 'La marca de la tienda.',
        'is_active' => true,
    ])->assertRedirect();

    $brand = Brand::query()->where('name', 'Acme')->firstOrFail();

    expect($brand->slug)->toBe('acme')
        ->and($brand->description)->toBe('La marca de la tienda.');
});

it('leaves the products of a brand without a brand when it is removed', function () {
    staffOfCatalog();

    $brand = Brand::factory()->create();
    $product = Product::factory()->create(['brand_id' => $brand->getKey()]);

    $this->delete('/sivarcart/admin/brands/'.$brand->getKey())->assertRedirect();

    expect($product->fresh()->brand_id)->toBeNull();
});

it('writes a shipping method of the store', function () {
    staffOfCatalog();

    $this->post('/sivarcart/admin/shipping-methods', [
        'name' => 'Envío estándar',
        'code' => 'standard',
        'type' => 'flat_rate',
        'price' => '5.00',
        'estimated_days' => 3,
        'is_active' => true,
    ])->assertRedirect();

    $method = ShippingMethod::query()->where('code', 'standard')->firstOrFail();

    expect((float) $method->price)->toBe(5.0)
        ->and($method->estimated_days)->toBe(3);
});

it('builds the code of a shipping method when the staff does not write one', function () {
    staffOfCatalog();

    $this->post('/sivarcart/admin/shipping-methods', [
        'name' => 'Recoger en tienda',
        'type' => 'pickup',
        'price' => '0',
        'is_active' => true,
    ])->assertRedirect();

    expect(ShippingMethod::query()->where('code', 'recoger_en_tienda')->exists())->toBeTrue();
});

it('writes a payment method of the store with its credentials', function () {
    staffOfCatalog();

    $this->post('/sivarcart/admin/payment-methods', [
        'name' => 'Wompi',
        'code' => 'wompi',
        'type' => 'online',
        'driver' => 'wompi',
        'is_active' => true,
        'credentials' => [
            ['key' => 'client_id', 'value' => 'el-id'],
            ['key' => 'client_secret', 'value' => 'el-secreto'],
        ],
    ])->assertRedirect();

    $method = PaymentMethod::query()->where('code', 'wompi')->firstOrFail();

    expect($method->driver)->toBe('wompi')
        ->and($method->credentials)->toBe([
            'client_id' => 'el-id',
            'client_secret' => 'el-secreto',
        ]);
});

it('never sends the credentials of a payment method to the browser', function () {
    staffOfCatalog();

    $method = PaymentMethod::factory()->create([
        'credentials' => ['client_secret' => 'el-secreto'],
    ]);

    $this->get('/sivarcart/admin/payment-methods')
        ->assertInertia(fn (Assert $page) => $page
            ->where('methods.data.0.has_credentials', true)
            ->missing('methods.data.0.credentials'),
        );

    $this->get('/sivarcart/admin/payment-methods/'.$method->uuid.'/edit')
        ->assertInertia(fn (Assert $page) => $page
            ->where('credentials.0.key', 'client_secret')
            ->where('credentials.0.value', ''),
        );
});

it('does not write the catalog to the staff that cannot', function () {
    staffOfCatalog(['orders.view']);

    $this->post('/sivarcart/admin/categories', [
        'name' => 'Ropa',
        'is_active' => true,
    ])->assertForbidden();

    $this->post('/sivarcart/admin/brands', [
        'name' => 'Acme',
        'is_active' => true,
    ])->assertForbidden();

    $this->post('/sivarcart/admin/shipping-methods', [
        'name' => 'Envío',
        'type' => 'flat_rate',
        'price' => '5',
        'is_active' => true,
    ])->assertForbidden();

    $this->post('/sivarcart/admin/payment-methods', [
        'name' => 'Wompi',
        'type' => 'offline',
        'is_active' => true,
    ])->assertForbidden();
});

it('shows the pages of the catalog of the panel', function () {
    staffOfCatalog();

    $this->get('/sivarcart/admin/categories')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Admin/Categories/Index'));

    $this->get('/sivarcart/admin/brands')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Admin/Brands/Index'));

    $this->get('/sivarcart/admin/shipping-methods')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Admin/ShippingMethods/Index'));

    $this->get('/sivarcart/admin/payment-methods')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Admin/PaymentMethods/Index'));
});

it('shows a product of the catalog with its variants', function () {
    staffOfCatalog();

    $product = Product::factory()->variable()->create(['name' => 'Camiseta azul']);

    ProductVariant::factory()->create([
        'product_id' => $product->getKey(),
        'name' => 'Roja',
        'price' => 12,
        'is_default' => true,
    ]);

    $this->get('/sivarcart/admin/products/'.$product->uuid.'/edit')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Products/Form')
            ->where('product.name', 'Camiseta azul')
            ->where('product.type', ProductType::VARIABLE->value)
            ->has('product.variants', 1)
            ->where('product.variants.0.name', 'Roja'),
        );
});

it('answers 404 for a product that does not exist', function () {
    staffOfCatalog();

    $this->get('/sivarcart/admin/products/un-producto-que-no-existe/edit')->assertNotFound();
});
