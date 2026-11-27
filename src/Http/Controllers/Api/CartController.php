<?php

declare(strict_types=1);

namespace Tarkontech\SivarCart\Http\Controllers\Api;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Tarkontech\SivarCart\Http\Requests\ApplyCouponRequest;
use Tarkontech\SivarCart\Http\Requests\StoreCartItemRequest;
use Tarkontech\SivarCart\Http\Requests\UpdateCartItemRequest;
use Tarkontech\SivarCart\Http\Resources\CartResource;
use Tarkontech\SivarCart\Models\Cart;
use Tarkontech\SivarCart\Models\CartItem;
use Tarkontech\SivarCart\Models\ProductVariant;
use Tarkontech\SivarCart\Services\CartService;

/**
 * Keeps the cart of a shopper.
 *
 * The cart belongs to the buyer that is signed in, so a line of another
 * buyer is not found: the answer is a 404 and never the line itself.
 */
final class CartController extends ApiController
{
    public function __construct(private readonly CartService $carts)
    {
        //
    }

    /**
     * The cart of the buyer.
     */
    public function show(): JsonResponse
    {
        // The cart is created the first time the buyer asks for it, and the
        // framework answers a 201 when the model of a resource is new. The
        // buyer asked to read the cart, so the answer is a 200.
        return CartResource::make($this->cart())->response()->setStatusCode(200);
    }

    /**
     * Put a variant in the cart.
     */
    public function addItem(StoreCartItemRequest $request): JsonResponse
    {
        $cart = $this->cart();

        $variant = ProductVariant::query()
            ->active()
            ->where('id', (int) $request->validated('variant_id'))
            ->whereHas('product', fn (Builder $query): Builder => $query->published())
            ->firstOrFail();

        $this->carts->addItem($cart, $variant, (int) $request->validated('quantity', 1));

        return CartResource::make($this->cart())->response()->setStatusCode(201);
    }

    /**
     * Change the units of a line of the cart.
     */
    public function updateItem(UpdateCartItemRequest $request, string $item): JsonResponse
    {
        $cart = $this->cart();

        $this->carts->updateQuantity(
            $cart,
            $this->line($cart, $item),
            (int) $request->validated('quantity'),
        );

        return CartResource::make($this->cart())->response();
    }

    /**
     * Take a line out of the cart.
     */
    public function removeItem(string $item): JsonResponse
    {
        $cart = $this->cart();

        $this->carts->removeItem($cart, $this->line($cart, $item));

        return CartResource::make($this->cart())->response();
    }

    /**
     * Apply a discount code to the cart.
     */
    public function applyCoupon(ApplyCouponRequest $request): JsonResponse
    {
        $cart = $this->cart();

        $this->carts->applyCoupon($cart, (string) $request->validated('code'));

        return CartResource::make($this->cart())->response();
    }

    /**
     * Take the discount code out of the cart.
     */
    public function removeCoupon(): JsonResponse
    {
        $cart = $this->cart();

        $this->carts->removeCoupon($cart);

        return CartResource::make($this->cart())->response();
    }

    /**
     * Empty the cart of the buyer.
     */
    public function clear(): JsonResponse
    {
        $cart = $this->cart();

        $this->carts->clear($cart);

        return CartResource::make($this->cart())->response();
    }

    /**
     * The cart of the buyer, with its lines and its coupon.
     */
    private function cart(): Cart
    {
        $buyer = $this->buyer();

        $cart = $this->carts->findOrCreate((int) $buyer->getKey());

        return $cart->load(['items', 'coupon']);
    }

    /**
     * A line of the cart of the buyer.
     */
    private function line(Cart $cart, string $item): CartItem
    {
        return $cart->items()
            ->whereKey((int) $item)
            ->firstOrFail();
    }
}
