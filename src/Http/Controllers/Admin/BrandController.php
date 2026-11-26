<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use SivarcartCore\SivarcartCore\Admin\AdminPresenter;
use SivarcartCore\SivarcartCore\Models\Brand;
use SivarcartCore\SivarcartCore\Services\Admin\BrandService;
use SivarcartCore\SivarcartCore\Support\Auth;

/**
 * Writes the brands of the catalog.
 *
 * The brands of the catalog are addressed by their identifier and not by a
 * uuid, the same way the store API addresses them.
 */
final class BrandController
{
    /**
     * Show the form of a new brand.
     */
    public function create(): Response
    {
        return $this->form(null);
    }

    /**
     * Create the brand the staff wrote.
     */
    public function store(Request $request): RedirectResponse
    {
        $attributes = $request->validate($this->rules());

        $brand = app(BrandService::class)->create($attributes);

        Inertia::flash('notice', __('sivarcart-core::admin.brands.created', [
            'name' => (string) $brand->name,
        ]));

        return redirect()->route('sivarcart-core.admin.brands.edit', [
            'brand' => (int) $brand->getKey(),
        ])->setStatusCode(303);
    }

    /**
     * Show the form of a brand.
     */
    public function edit(int $brand): Response
    {
        $model = app(BrandService::class)->find($brand);

        if (! $model instanceof Brand) {
            abort(404, __('sivarcart-core::admin.brands.not_found'));
        }

        return $this->form($model);
    }

    /**
     * Write the brand the staff changed.
     */
    public function update(Request $request, int $brand): RedirectResponse
    {
        $model = app(BrandService::class)->find($brand);

        if (! $model instanceof Brand) {
            abort(404, __('sivarcart-core::admin.brands.not_found'));
        }

        $attributes = $request->validate($this->rules());

        app(BrandService::class)->update($model, $attributes);

        Inertia::flash('notice', __('sivarcart-core::admin.brands.saved', [
            'name' => (string) $model->name,
        ]));

        return back()->setStatusCode(303);
    }

    /**
     * Remove the brand from the catalog.
     */
    public function destroy(int $brand): RedirectResponse
    {
        $model = app(BrandService::class)->find($brand);

        if (! $model instanceof Brand) {
            abort(404, __('sivarcart-core::admin.brands.not_found'));
        }

        app(BrandService::class)->delete($model);

        Inertia::flash('notice', __('sivarcart-core::admin.brands.deleted', [
            'name' => (string) $model->name,
        ]));

        return redirect()->route('sivarcart-core.admin.page', ['page' => 'brands'])
            ->setStatusCode(303);
    }

    /**
     * The form of a brand.
     */
    private function form(?Brand $brand): Response
    {
        return Inertia::render('Admin/Brands/Form', [
            'brand' => $brand instanceof Brand ? AdminPresenter::brand($brand) : null,
            'canUpdate' => Auth::hasStorePermission(Auth::user(), 'catalog.update'),
        ]);
    }

    /**
     * The rules of the form of a brand.
     *
     * @return array<string, mixed>
     */
    private function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'position' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['boolean'],
        ];
    }
}
