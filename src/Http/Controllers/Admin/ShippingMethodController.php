<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use SivarcartCore\SivarcartCore\Admin\AdminPresenter;
use SivarcartCore\SivarcartCore\Enums\ShippingMethodType;
use SivarcartCore\SivarcartCore\Models\ShippingMethod;
use SivarcartCore\SivarcartCore\Services\Admin\ShippingMethodService;
use SivarcartCore\SivarcartCore\Support\Auth;

/**
 * Writes the shipping methods of the store.
 */
final class ShippingMethodController
{
    /**
     * Show the form of a new shipping method.
     */
    public function create(): Response
    {
        return $this->form(null);
    }

    /**
     * Create the shipping method the staff wrote.
     */
    public function store(Request $request): RedirectResponse
    {
        $attributes = $request->validate($this->rules());

        $method = app(ShippingMethodService::class)->create($attributes);

        Inertia::flash('notice', __('sivarcart-core::admin.shipping_methods.created', [
            'name' => (string) $method->name,
        ]));

        return redirect()->route('sivarcart-core.admin.shipping-methods.edit', [
            'method' => (string) $method->uuid,
        ])->setStatusCode(303);
    }

    /**
     * Show the form of a shipping method.
     */
    public function edit(string $method): Response
    {
        $model = app(ShippingMethodService::class)->find($method);

        if (! $model instanceof ShippingMethod) {
            abort(404, __('sivarcart-core::admin.shipping_methods.not_found'));
        }

        return $this->form($model);
    }

    /**
     * Write the shipping method the staff changed.
     */
    public function update(Request $request, string $method): RedirectResponse
    {
        $model = app(ShippingMethodService::class)->find($method);

        if (! $model instanceof ShippingMethod) {
            abort(404, __('sivarcart-core::admin.shipping_methods.not_found'));
        }

        $attributes = $request->validate($this->rules($model));

        app(ShippingMethodService::class)->update($model, $attributes);

        Inertia::flash('notice', __('sivarcart-core::admin.shipping_methods.saved', [
            'name' => (string) $model->name,
        ]));

        return back()->setStatusCode(303);
    }

    /**
     * Remove the shipping method from the store.
     */
    public function destroy(string $method): RedirectResponse
    {
        $model = app(ShippingMethodService::class)->find($method);

        if (! $model instanceof ShippingMethod) {
            abort(404, __('sivarcart-core::admin.shipping_methods.not_found'));
        }

        app(ShippingMethodService::class)->delete($model);

        Inertia::flash('notice', __('sivarcart-core::admin.shipping_methods.deleted', [
            'name' => (string) $model->name,
        ]));

        return redirect()->route('sivarcart-core.admin.page', ['page' => 'shipping-methods'])
            ->setStatusCode(303);
    }

    /**
     * The form of a shipping method.
     */
    private function form(?ShippingMethod $method): Response
    {
        $types = [];

        foreach (ShippingMethodType::cases() as $type) {
            $types[] = AdminPresenter::state($type->value, $type->label());
        }

        return Inertia::render('Admin/ShippingMethods/Form', [
            'method' => $method instanceof ShippingMethod
                ? AdminPresenter::shippingMethod($method)
                : null,
            'types' => $types,
            'canUpdate' => Auth::hasStorePermission(Auth::user(), 'catalog.update'),
        ]);
    }

    /**
     * The rules of the form of a shipping method.
     *
     * The code of a method is unique in the store, and the method that is
     * being written holds it already, so it is left out of the check.
     *
     * @return array<string, mixed>
     */
    private function rules(?ShippingMethod $method = null): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'nullable',
                'string',
                'max:64',
                Rule::unique('sivarcart_shipping_methods', 'code')->ignore($method?->getKey()),
            ],
            'type' => ['required', Rule::enum(ShippingMethodType::class)],
            'price' => ['required', 'numeric', 'min:0'],
            'min_order_amount' => ['nullable', 'numeric', 'min:0'],
            'max_order_amount' => ['nullable', 'numeric', 'min:0'],
            'estimated_days' => ['nullable', 'integer', 'min:0'],
            'instructions' => ['nullable', 'string'],
            'position' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['boolean'],
        ];
    }
}
