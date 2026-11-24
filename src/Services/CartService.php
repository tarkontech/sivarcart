<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Services;

use Illuminate\Database\Eloquent\Builder;
use SivarcartCore\SivarcartCore\Exceptions\CouponNotApplicable;
use SivarcartCore\SivarcartCore\Exceptions\SivarcartCoreException;
use SivarcartCore\SivarcartCore\Models\Cart;
use SivarcartCore\SivarcartCore\Models\CartItem;
use SivarcartCore\SivarcartCore\Models\ProductVariant;

/**
 * Builds and keeps the cart of a shopper.
 *
 * The totals of a cart are only a cache of what its items are worth: they are
 * recalculated from the items every time the cart changes, and they are never
 * trusted on their own when the order is placed.
 *
 * @phpstan-import-type PriceLineInput from PricingService
 */
final class CartService
{
    public function __construct(
        private readonly PricingService $pricing,
        private readonly CouponService $coupons,
        private readonly StoreService $stores,
    ) {
        //
    }

    /**
     * The cart of a customer, creating it when it is the first visit.
     *
     * A cart always belongs to a buyer: the buyer signs in before buying, so
     * there is no cart that belongs to a session.
     *
     * A cart that ran out of time is not handed back: the buyer gets a new
     * one and the old one is left for the pruning command to remove it. A cart
     * without an expiration date never runs out of time.
     *
     * A cart that already became an order is not handed back either: the
     * buyer that comes back to the store gets a new cart, and the old one is
     * left for the pruning command to remove it.
     */
    public function findOrCreate(int $customerId): Cart
    {
        $cart = Cart::query()
            ->where('customer_id', $customerId)
            ->whereNull('converted_at')
            ->where(function (Builder $query): void {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->orderByDesc('id')
            ->first();

        if ($cart !== null) {
            return $cart;
        }

        return Cart::query()->create([
            'customer_id' => $customerId,
            'currency' => $this->stores->currency(),
            'expires_at' => now()->addDays($this->ttlDays()),
        ]);
    }

    /**
     * Put a variant in the cart, adding it to the units it already had.
     */
    public function addItem(Cart $cart, ProductVariant $variant, int $quantity = 1): CartItem
    {
        if ($quantity < 1) {
            throw new SivarcartCoreException('La cantidad de un producto tiene que ser mayor que cero.');
        }

        $item = $cart->items()
            ->where('product_variant_id', $variant->getKey())
            ->first();

        if ($item === null) {
            $item = $cart->items()->create([
                'product_id' => $variant->product_id,
                'product_variant_id' => $variant->getKey(),
                'name' => $this->nameOf($variant),
                'sku' => $variant->sku,
                'options' => [],
                'unit_price' => (float) $variant->price,
                'quantity' => $quantity,
            ]);
        } else {
            $item->quantity = $item->quantity + $quantity;
            $item->save();
        }

        $this->recalculate($cart);

        return $item;
    }

    /**
     * Change the units of an item, removing it when there are none left.
     */
    public function updateQuantity(Cart $cart, CartItem $item, int $quantity): void
    {
        if ($quantity < 1) {
            $this->removeItem($cart, $item);

            return;
        }

        $item->quantity = $quantity;
        $item->save();

        $this->recalculate($cart);
    }

    /**
     * Take an item out of the cart.
     */
    public function removeItem(Cart $cart, CartItem $item): void
    {
        $item->delete();

        $this->recalculate($cart);
    }

    /**
     * Apply a discount code to the cart.
     *
     * @throws CouponNotApplicable
     */
    public function applyCoupon(Cart $cart, string $code): Cart
    {
        $coupon = $this->coupons->find($code);

        if ($coupon === null) {
            throw CouponNotApplicable::unknownCode($code);
        }

        $subtotal = $this->subtotal($this->lines($cart));

        $this->coupons->assertApplicable($coupon, $subtotal, $cart->customer_id);

        $cart->coupon_id = $coupon->getKey();
        $cart->save();

        // The relation may already be loaded with the previous coupon, so it
        // is dropped to let the totals be read from the database again.
        $cart->unsetRelation('coupon');

        return $this->recalculate($cart);
    }

    /**
     * Take the discount code out of the cart.
     */
    public function removeCoupon(Cart $cart): Cart
    {
        $cart->coupon_id = null;
        $cart->save();

        $cart->unsetRelation('coupon');

        return $this->recalculate($cart);
    }

    /**
     * The lines of the cart, with the tax rate that applies to each of them.
     *
     * @return list<PriceLineInput>
     */
    public function lines(Cart $cart): array
    {
        $defaultRate = $this->stores->defaultTaxRate();
        $lines = [];

        foreach ($cart->items()->get() as $item) {
            $lines[] = [
                'unit_price' => (float) $item->unit_price,
                'quantity' => (int) $item->quantity,
                'tax_rate' => $this->pricing->taxRateFor($item->product, $defaultRate),
            ];
        }

        return $lines;
    }

    /**
     * Recalculate the totals of the cart from its items.
     */
    public function recalculate(Cart $cart): Cart
    {
        $items = $cart->items()->get();
        $lines = $this->lines($cart);
        $taxInclusive = $this->stores->pricesIncludeTax();

        $coupon = $cart->coupon;
        $subtotal = $this->subtotal($lines);

        $discount = $coupon === null ? 0.0 : $this->coupons->discountFor($coupon, $subtotal);

        $priced = $this->pricing->price($lines, $taxInclusive, $discount);
        $totals = $this->pricing->totals($priced, 0.0, 0.0, $taxInclusive);

        $index = 0;

        foreach ($items as $item) {
            $item->update([
                'line_subtotal' => $priced[$index]['line_subtotal'],
                'discount_total' => $priced[$index]['discount_total'],
                'tax_total' => $priced[$index]['tax_total'],
                'line_total' => $priced[$index]['line_total'],
            ]);

            $index++;
        }

        $cart->update([
            'subtotal' => $totals->subtotal,
            'discount_total' => $totals->discountTotal,
            'tax_total' => $totals->taxTotal,
            'shipping_total' => $totals->shippingTotal,
            'total' => $totals->total,
            'item_count' => (int) $items->sum('quantity'),
        ]);

        return $cart;
    }

    /**
     * Empty the cart.
     */
    public function clear(Cart $cart): Cart
    {
        $cart->items()->delete();
        $cart->coupon_id = null;
        $cart->save();

        return $this->recalculate($cart);
    }

    /**
     * How many days a cart lives before it expires.
     */
    private function ttlDays(): int
    {
        return (int) config('sivarcart-core.cart.ttl_days', 30);
    }

    /**
     * The sum of the lines before any discount.
     *
     * @param  list<PriceLineInput>  $lines
     */
    private function subtotal(array $lines): float
    {
        $subtotal = 0.0;

        foreach ($lines as $line) {
            $subtotal += $line['unit_price'] * $line['quantity'];
        }

        return round($subtotal, 2);
    }

    /**
     * The name the item of the cart shows.
     */
    private function nameOf(ProductVariant $variant): string
    {
        if (is_string($variant->name) && $variant->name !== '') {
            return $variant->name;
        }

        $product = $variant->product;

        if ($product !== null) {
            return $product->name;
        }

        return 'Producto';
    }
}
