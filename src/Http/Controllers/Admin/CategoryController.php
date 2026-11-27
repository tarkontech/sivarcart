<?php

declare(strict_types=1);

namespace Tarkontech\SivarCart\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Tarkontech\SivarCart\Admin\AdminPresenter;
use Tarkontech\SivarCart\Exceptions\InvalidCategoryParent;
use Tarkontech\SivarCart\Models\Category;
use Tarkontech\SivarCart\Services\Admin\CategoryService;
use Tarkontech\SivarCart\Support\Auth;

/**
 * Writes the categories of the catalog.
 */
final class CategoryController
{
    /**
     * Show the form of a new category.
     */
    public function create(): Response
    {
        return $this->form(null);
    }

    /**
     * Create the category the staff wrote.
     */
    public function store(Request $request): RedirectResponse
    {
        $attributes = $request->validate($this->rules());

        try {
            $category = app(CategoryService::class)->create($attributes);
        } catch (InvalidCategoryParent $exception) {
            return back()
                ->withErrors(['parent_id' => $exception->getMessage()])
                ->setStatusCode(303);
        }

        Inertia::flash('notice', __('sivarcart::admin.categories.created', [
            'name' => (string) $category->name,
        ]));

        return redirect()->route('sivarcart.admin.categories.edit', [
            'category' => (string) $category->uuid,
        ])->setStatusCode(303);
    }

    /**
     * Show the form of a category.
     */
    public function edit(string $category): Response
    {
        $model = app(CategoryService::class)->find($category);

        if (! $model instanceof Category) {
            abort(404, __('sivarcart::admin.categories.not_found'));
        }

        return $this->form($model);
    }

    /**
     * Write the category the staff changed.
     */
    public function update(Request $request, string $category): RedirectResponse
    {
        $model = app(CategoryService::class)->find($category);

        if (! $model instanceof Category) {
            abort(404, __('sivarcart::admin.categories.not_found'));
        }

        $attributes = $request->validate($this->rules());

        try {
            app(CategoryService::class)->update($model, $attributes);
        } catch (InvalidCategoryParent $exception) {
            return back()
                ->withErrors(['parent_id' => $exception->getMessage()])
                ->setStatusCode(303);
        }

        Inertia::flash('notice', __('sivarcart::admin.categories.saved', [
            'name' => (string) $model->name,
        ]));

        return back()->setStatusCode(303);
    }

    /**
     * Take the category off the catalog.
     */
    public function destroy(string $category): RedirectResponse
    {
        $model = app(CategoryService::class)->find($category);

        if (! $model instanceof Category) {
            abort(404, __('sivarcart::admin.categories.not_found'));
        }

        app(CategoryService::class)->delete($model);

        Inertia::flash('notice', __('sivarcart::admin.categories.deleted', [
            'name' => (string) $model->name,
        ]));

        return redirect()->route('sivarcart.admin.page', ['page' => 'categories'])
            ->setStatusCode(303);
    }

    /**
     * The form of a category.
     */
    private function form(?Category $category): Response
    {
        $categories = [];

        foreach (app(CategoryService::class)->all() as $parent) {
            $categories[] = AdminPresenter::category($parent);
        }

        return Inertia::render('Admin/Categories/Form', [
            'category' => $category instanceof Category
                ? AdminPresenter::category($category)
                : null,
            'categories' => $categories,
            'canUpdate' => Auth::hasStorePermission(Auth::user(), 'catalog.update'),
        ]);
    }

    /**
     * The rules of the form of a category.
     *
     * @return array<string, mixed>
     */
    private function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'icon' => ['nullable', 'string', 'max:255'],
            'parent_id' => ['nullable', 'integer', 'exists:sivarcart_categories,id'],
            'position' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['boolean'],
            'is_featured' => ['boolean'],
        ];
    }
}
