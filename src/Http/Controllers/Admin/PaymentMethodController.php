<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use SivarcartCore\SivarcartCore\Admin\AdminPresenter;
use SivarcartCore\SivarcartCore\Enums\PaymentMethodType;
use SivarcartCore\SivarcartCore\Models\PaymentMethod;
use SivarcartCore\SivarcartCore\Services\Admin\PaymentMethodService;
use SivarcartCore\SivarcartCore\Support\Auth;

/**
 * Writes the payment methods of the store.
 *
 * The credentials of a method never come back to the browser: the panel is
 * told which keys the method has, and the staff writes the values again when
 * they want to change them.
 */
final class PaymentMethodController
{
    /**
     * Show the form of a new payment method.
     */
    public function create(): Response
    {
        return $this->form(null);
    }

    /**
     * Create the payment method the staff wrote.
     */
    public function store(Request $request): RedirectResponse
    {
        $attributes = $request->validate($this->rules());

        $method = app(PaymentMethodService::class)->create($attributes);

        Inertia::flash('notice', __('sivarcart-core::admin.payment_methods.created', [
            'name' => (string) $method->name,
        ]));

        return redirect()->route('sivarcart-core.admin.payment-methods.edit', [
            'method' => (string) $method->uuid,
        ])->setStatusCode(303);
    }

    /**
     * Show the form of a payment method.
     */
    public function edit(string $method): Response
    {
        $model = app(PaymentMethodService::class)->find($method);

        if (! $model instanceof PaymentMethod) {
            abort(404, __('sivarcart-core::admin.payment_methods.not_found'));
        }

        return $this->form($model);
    }

    /**
     * Write the payment method the staff changed.
     */
    public function update(Request $request, string $method): RedirectResponse
    {
        $model = app(PaymentMethodService::class)->find($method);

        if (! $model instanceof PaymentMethod) {
            abort(404, __('sivarcart-core::admin.payment_methods.not_found'));
        }

        $attributes = $request->validate($this->rules($model));

        app(PaymentMethodService::class)->update($model, $attributes);

        Inertia::flash('notice', __('sivarcart-core::admin.payment_methods.saved', [
            'name' => (string) $model->name,
        ]));

        return back()->setStatusCode(303);
    }

    /**
     * Take the payment method off the store.
     */
    public function destroy(string $method): RedirectResponse
    {
        $model = app(PaymentMethodService::class)->find($method);

        if (! $model instanceof PaymentMethod) {
            abort(404, __('sivarcart-core::admin.payment_methods.not_found'));
        }

        app(PaymentMethodService::class)->delete($model);

        Inertia::flash('notice', __('sivarcart-core::admin.payment_methods.deleted', [
            'name' => (string) $model->name,
        ]));

        return redirect()->route('sivarcart-core.admin.page', ['page' => 'payment-methods'])
            ->setStatusCode(303);
    }

    /**
     * The form of a payment method.
     */
    private function form(?PaymentMethod $method): Response
    {
        $types = [];

        foreach (PaymentMethodType::cases() as $type) {
            $types[] = AdminPresenter::state($type->value, $type->label());
        }

        return Inertia::render('Admin/PaymentMethods/Form', [
            'method' => $method instanceof PaymentMethod
                ? AdminPresenter::paymentMethod($method)
                : null,
            'credentials' => $method instanceof PaymentMethod
                ? AdminPresenter::paymentCredentials($method)
                : [],
            'types' => $types,
            'canUpdate' => Auth::hasStorePermission(Auth::user(), 'catalog.update'),
        ]);
    }

    /**
     * The rules of the form of a payment method.
     *
     * The code of a method is unique in the store, and the method that is
     * being written holds it already, so it is left out of the check.
     *
     * @return array<string, mixed>
     */
    private function rules(?PaymentMethod $method = null): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'nullable',
                'string',
                'max:64',
                Rule::unique('sivarcart_payment_methods', 'code')->ignore($method?->getKey()),
            ],
            'type' => ['required', Rule::enum(PaymentMethodType::class)],
            'driver' => ['nullable', 'string', 'max:64'],
            'instructions' => ['nullable', 'string'],
            'icon' => ['nullable', 'string', 'max:255'],
            'position' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['boolean'],
            'credentials' => ['array'],
            'credentials.*.key' => ['required', 'string', 'max:255'],
            'credentials.*.value' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
