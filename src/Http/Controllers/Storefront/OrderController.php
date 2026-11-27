<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Http\Controllers\Storefront;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use SivarcartCore\SivarcartCore\Actions\StartPayment;
use SivarcartCore\SivarcartCore\Exceptions\PaymentGatewayFailed;
use SivarcartCore\SivarcartCore\Models\Order;
use SivarcartCore\SivarcartCore\Services\CustomerService;
use SivarcartCore\SivarcartCore\Storefront\StorefrontPresenter;

/**
 * The orders of the shopper.
 *
 * An order belongs to the buyer that placed it, so the order of another
 * buyer is not found: the answer is a 404 and never the order itself.
 */
final class OrderController
{
    public function __construct(
        private readonly CustomerService $customers,
        private readonly StartPayment $startPayment,
    ) {
        //
    }

    /**
     * The orders of the shopper.
     */
    public function index(Request $request): Response
    {
        $buyer = $this->customers->user();

        $orders = Order::query()
            ->where('customer_id', $buyer->getKey())
            ->with(['items', 'payments'])
            ->orderByDesc('placed_at')
            ->orderByDesc('id')
            ->paginate(max(1, min($request->integer('per_page', 15), 100)));

        return Inertia::render('Storefront/Orders/Index', [
            'orders' => StorefrontPresenter::paginate(
                $orders,
                fn (Order $order): array => StorefrontPresenter::order($order),
            ),
        ]);
    }

    /**
     * The page of an order.
     *
     * It is the screen de confirmación when the order was just placed.
     */
    public function show(string $order): Response
    {
        $model = $this->order($order)->load(['items', 'payments']);

        return Inertia::render('Storefront/Orders/Show', [
            'order' => StorefrontPresenter::order($model),
        ]);
    }

    /**
     * The link the buyer pays the order with.
     */
    public function payment(string $order): RedirectResponse
    {
        $model = $this->order($order);

        try {
            $result = $this->startPayment->execute($model);
        } catch (PaymentGatewayFailed $exception) {
            return back()->withErrors(['payment' => $exception->getMessage()]);
        }

        return redirect()
            ->route('sivarcart-core.storefront.orders.show', ['order' => $model->uuid])
            ->with('payment_link', [
                'url' => $result->url,
                'reference' => $result->reference,
            ]);
    }

    /**
     * An order of the shopper.
     */
    private function order(string $uuid): Order
    {
        $buyer = $this->customers->user();

        return Order::query()
            ->where('uuid', $uuid)
            ->where('customer_id', $buyer->getKey())
            ->firstOrFail();
    }
}
