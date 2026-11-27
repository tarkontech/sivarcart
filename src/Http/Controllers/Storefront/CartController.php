<?php

declare(strict_types=1);

namespace Tarkontech\SivarCart\Http\Controllers\Storefront;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Tarkontech\SivarCart\Exceptions\SivarCartException;
use Tarkontech\SivarCart\Http\Requests\ApplyCouponRequest;
use Tarkontech\SivarCart\Http\Requests\StorefrontCartItemRequest;
use Tarkontech\SivarCart\Http\Requests\UpdateCartItemRequest;
use Tarkontech\SivarCart\Models\Cart;
use Tarkontech\SivarCart\Models\CartItem;
use Tarkontech\SivarCart\Models\ProductVariant;
use Tarkontech\SivarCart\Services\CartService;
use Tarkontech\SivarCart\Services\CustomerService;
use Tarkontech\SivarCart\Storefront\StorefrontPresenter;

/**
 * Keeps the cart of the shopper.
 *
 * The cart belongs to the buyer that is signed in, so a line of another
 * buyer is not found: the answer is a 404 and never the line itself.
 */
final class CartController
{
    public function __construct(
        private readonly CartService $carts,
        private readonly CustomerService $customers,
    ) {
        //
    }

    /**
     * The cart of the shopper.
     */
    public function show(): Response
    {
        return Inertia::render('Storefront/Cart/Index', [
            'cart' => StorefrontPresenter::cart($this->cart()),
        ]);
    }

    /**
     * Put a variant in the cart.
     */
    public function addItem(StorefrontCartItemRequest $request): RedirectResponse
    {
        $cart = $this->cart();

        $variant = ProductVariant::query()
            ->active()
            ->where('uuid', (string) $request->validated('variant_id'))
            ->whereHas('product', fn (Builder $query): Builder => $query->published())
            ->firstOrFail();

        try {
            $this->carts->addItem($cart, $variant, (int) $request->validated('quantity', 1));
        } catch (SivarCartException $exception) {
            return back()->withErrors(['variant_id' => $exception->getMessage()]);
        }

        return back()->with('flash', ['type' => 'success']);
    }

    /**
     * Change the units of a line of the cart.
     */
    public function updateItem(UpdateCartItemRequest $request, string $item): RedirectResponse
    {
        $cart = $this->cart();

        try {
            $this->carts->updateQuantity(
                $cart,
                $this->line($cart, $item),
                (int) $request->validated('quantity'),
            );
        } catch (SivarCartException $exception) {
            return back()->withErrors(['quantity' => $exception->getMessage()]);
        }

        return back()->with('flash', ['type' => 'success']);
    }

    /**
     * Take a line out of the cart.
     */
    public function removeItem(string $item): RedirectResponse
    {
        $cart = $this->cart();

        $this->carts->removeItem($cart, $this->line($cart, $item));

        return back()->with('flash', ['type' => 'success']);
    }

    /**
     * Apply a discount code to the cart.
     */
    public function applyCoupon(ApplyCouponRequest $request): RedirectResponse
    {
        $cart = $this->cart();

        try {
            $this->carts->applyCoupon($cart, (string) $request->validated('code'));
        } catch (SivarCartException $exception) {
            return back()->withErrors(['code' => $exception->getMessage()]);
        }

        return back()->with('flash', ['type' => 'success']);
    }

    /**
     * Take the discount code out of the cart.
     */
    public function removeCoupon(): RedirectResponse
    {
        $cart = $this->cart();

        $this->carts->removeCoupon($cart);

        return back()->with('flash', ['type' => 'success']);
    }

    /**
     * Empty the cart of the shopper.
     */
    public function clear(): RedirectResponse
    {
        $cart = $this->cart();

        $this->carts->clear($cart);

        return back()->with('flash', ['type' => 'success']);
    }

    /**
     * The cart of the shopper, with its lines and its coupon.
     */
    private function cart(): Cart
    {
        $buyer = $this->customers->user();

        $cart = $this->carts->findOrCreate((int) $buyer->getKey());

        return $cart->load(['items', 'coupon']);
    }

    /**
     * A line of the cart of the shopper.
     */
    private function line(Cart $cart, string $item): CartItem
    {
        return $cart->items()
            ->whereKey((int) $item)
            ->firstOrFail();
    }
}
