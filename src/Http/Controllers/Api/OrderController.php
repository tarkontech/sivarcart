<?php

declare(strict_types=1);

namespace Tarkontech\SivarCart\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Tarkontech\SivarCart\Actions\StartPayment;
use Tarkontech\SivarCart\Http\Resources\OrderResource;
use Tarkontech\SivarCart\Models\Order;

/**
 * The orders of a shopper.
 *
 * An order belongs to the buyer that placed it, so the order of another buyer
 * is not found: the answer is a 404 and never the order itself.
 */
final class OrderController extends ApiController
{
    public function __construct(private readonly StartPayment $startPayment)
    {
        //
    }

    /**
     * The orders of the buyer.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $buyer = $this->buyer();

        $orders = Order::query()
            ->where('customer_id', $buyer->getKey())
            ->with(['items', 'payments'])
            ->orderByDesc('placed_at')
            ->orderByDesc('id')
            ->paginate(max(1, min($request->integer('per_page', 15), 100)));

        return OrderResource::collection($orders);
    }

    /**
     * An order of the buyer.
     */
    public function show(string $order): OrderResource
    {
        return OrderResource::make($this->order($order)->load(['items', 'payments']));
    }

    /**
     * The link the buyer pays the order with.
     */
    public function payment(string $order): JsonResponse
    {
        $result = $this->startPayment->execute($this->order($order));

        return response()->json([
            'url' => $result->url,
            'reference' => $result->reference,
        ]);
    }

    /**
     * An order of the buyer.
     */
    private function order(string $uuid): Order
    {
        $buyer = $this->buyer();

        return Order::query()
            ->where('uuid', $uuid)
            ->where('customer_id', $buyer->getKey())
            ->firstOrFail();
    }
}
