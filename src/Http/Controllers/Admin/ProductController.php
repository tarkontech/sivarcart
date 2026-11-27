<?php

declare(strict_types=1);

namespace Tarkontech\SivarCart\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Tarkontech\SivarCart\Admin\AdminPresenter;
use Tarkontech\SivarCart\Enums\ProductStatus;
use Tarkontech\SivarCart\Enums\ProductType;
use Tarkontech\SivarCart\Models\Product;
use Tarkontech\SivarCart\Models\ProductVariant;
use Tarkontech\SivarCart\Services\Admin\BrandService;
use Tarkontech\SivarCart\Services\Admin\CategoryService;
use Tarkontech\SivarCart\Services\Admin\ProductService;
use Tarkontech\SivarCart\Support\Auth;

/**
 * Writes the products of the catalog.
 *
 * The panel never writes the catalog by itself: the product and its variants
 * are written by the service of the products, the same one the panel reads.
 */
final class ProductController
{
    /**
     * Show the form of a new product.
     */
    public function create(): Response
    {
        return $this->form(null);
    }

    /**
     * Create the product the staff wrote.
     */
    public function store(Request $request): RedirectResponse
    {
        $attributes = $request->validate($this->rules($request));

        $product = app(ProductService::class)->create($attributes);

        Inertia::flash('notice', __('sivarcart::admin.products.created', [
            'name' => (string) $product->name,
        ]));

        return redirect()->route('sivarcart.admin.products.edit', [
            'product' => (string) $product->uuid,
        ])->setStatusCode(303);
    }

    /**
     * Show the form of a product.
     */
    public function edit(string $product): Response
    {
        $model = app(ProductService::class)->find($product);

        if (! $model instanceof Product) {
            abort(404, __('sivarcart::admin.products.not_found'));
        }

        return $this->form($model);
    }

    /**
     * Write the product the staff changed.
     */
    public function update(Request $request, string $product): RedirectResponse
    {
        $model = app(ProductService::class)->find($product);

        if (! $model instanceof Product) {
            abort(404, __('sivarcart::admin.products.not_found'));
        }

        $attributes = $request->validate($this->rules($request));

        app(ProductService::class)->update($model, $attributes);

        Inertia::flash('notice', __('sivarcart::admin.products.saved', [
            'name' => (string) $model->name,
        ]));

        return back()->setStatusCode(303);
    }

    /**
     * Take the product off the catalog.
     */
    public function destroy(string $product): RedirectResponse
    {
        $model = app(ProductService::class)->find($product);

        if (! $model instanceof Product) {
            abort(404, __('sivarcart::admin.products.not_found'));
        }

        app(ProductService::class)->delete($model);

        Inertia::flash('notice', __('sivarcart::admin.products.deleted', [
            'name' => (string) $model->name,
        ]));

        return redirect()->route('sivarcart.admin.page', ['page' => 'products'])
            ->setStatusCode(303);
    }

    /**
     * The form of a product.
     *
     * The brands and the categories travel with the form, so the screen does
     * not have to ask for them again.
     */
    private function form(?Product $product): Response
    {
        $brands = [];

        foreach (app(BrandService::class)->all() as $brand) {
            $brands[] = AdminPresenter::brand($brand);
        }

        $categories = [];

        foreach (app(CategoryService::class)->all() as $category) {
            $categories[] = AdminPresenter::category($category);
        }

        $statuses = [];

        foreach (ProductStatus::cases() as $status) {
            $statuses[] = AdminPresenter::state($status->value, $status->label());
        }

        $types = [];

        foreach (ProductType::cases() as $type) {
            $types[] = AdminPresenter::state($type->value, $type->label());
        }

        return Inertia::render('Admin/Products/Form', [
            'product' => AdminPresenter::productForm($product),
            'brands' => $brands,
            'categories' => $categories,
            'statuses' => $statuses,
            'types' => $types,
            'canUpdate' => Auth::hasStorePermission(Auth::user(), 'catalog.update'),
        ]);
    }

    /**
     * The rules of the form of a product.
     *
     * The sku of a variant is unique in the store, so the variant that is
     * being written is left out of the check.
     *
     * @return array<string, mixed>
     */
    private function rules(Request $request): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'short_description' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['required', Rule::enum(ProductStatus::class)],
            'type' => ['required', Rule::enum(ProductType::class)],
            'brand_id' => ['nullable', 'integer', 'exists:sivarcart_brands,id'],
            'weight' => ['nullable', 'numeric', 'min:0'],
            'is_featured' => ['boolean'],
            'is_virtual' => ['boolean'],
            'categories' => ['array'],
            'categories.*' => ['integer', 'exists:sivarcart_categories,id'],
            'variants' => ['array'],
            'variants.*.uuid' => ['nullable', 'string', 'max:64'],
            'variants.*.name' => ['nullable', 'string', 'max:255'],
            'variants.*.barcode' => ['nullable', 'string', 'max:255'],
            'variants.*.price' => ['required', 'numeric', 'min:0'],
            'variants.*.compare_at_price' => ['nullable', 'numeric', 'min:0'],
            'variants.*.cost_price' => ['nullable', 'numeric', 'min:0'],
            'variants.*.stock_quantity' => ['required', 'integer', 'min:0'],
            'variants.*.low_stock_threshold' => ['nullable', 'integer', 'min:0'],
            'variants.*.weight' => ['nullable', 'numeric', 'min:0'],
            'variants.*.is_default' => ['boolean'],
            'variants.*.is_active' => ['boolean'],
        ];

        foreach ($this->skuRules($request) as $key => $rule) {
            $rules[$key] = $rule;
        }

        return $rules;
    }

    /**
     * The rule of the sku of every variant of the form.
     *
     * The sku is unique in the whole store, and the variant that is being
     * written holds it already, so it is left out of the check.
     *
     * @return array<string, list<mixed>>
     */
    private function skuRules(Request $request): array
    {
        $variants = $request->input('variants');

        if (! is_array($variants)) {
            return [];
        }

        $uuids = [];

        foreach ($variants as $variant) {
            if (! is_array($variant)) {
                continue;
            }

            $uuid = $variant['uuid'] ?? null;

            if (is_string($uuid) && $uuid !== '') {
                $uuids[] = $uuid;
            }
        }

        $ids = $uuids === []
            ? []
            : ProductVariant::query()->whereIn('uuid', $uuids)->pluck('id', 'uuid')->all();

        $rules = [];

        foreach ($variants as $index => $variant) {
            if (! is_int($index)) {
                continue;
            }

            $uuid = is_array($variant) ? ($variant['uuid'] ?? null) : null;

            $ignore = is_string($uuid) ? ($ids[$uuid] ?? null) : null;

            $rule = ['nullable', 'string', 'max:255'];

            $rule[] = $ignore === null
                ? Rule::unique('sivarcart_product_variants', 'sku')
                : Rule::unique('sivarcart_product_variants', 'sku')->ignore($ignore);

            $rules['variants.'.$index.'.sku'] = $rule;
        }

        return $rules;
    }
}
