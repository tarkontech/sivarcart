<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Storefront;

use DateTimeInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;
use SivarcartCore\SivarcartCore\Models\Brand;
use SivarcartCore\SivarcartCore\Models\Cart;
use SivarcartCore\SivarcartCore\Models\CartItem;
use SivarcartCore\SivarcartCore\Models\Category;
use SivarcartCore\SivarcartCore\Models\Customer;
use SivarcartCore\SivarcartCore\Models\Order;
use SivarcartCore\SivarcartCore\Models\OrderItem;
use SivarcartCore\SivarcartCore\Models\Payment;
use SivarcartCore\SivarcartCore\Models\PaymentMethod;
use SivarcartCore\SivarcartCore\Models\Product;
use SivarcartCore\SivarcartCore\Models\ProductImage;
use SivarcartCore\SivarcartCore\Models\ProductVariant;
use SivarcartCore\SivarcartCore\Models\ShippingMethod;
use SivarcartCore\SivarcartCore\Support\Media;
use Throwable;

/**
 * The shape the store shows to the shopper.
 *
 * The store is an Inertia application and the props it receives are plain
 * data: a model is never handed to the browser as it is. This class is what
 * decides what leaves the server, so a field the store does not show —the
 * costo del producto, las notas de la orden, los metadatos de la tienda—
 * nunca viaja.
 *
 * The shapes are the ones the shopper reads: los precios van con su nombre,
 * las existencias son las que se pueden comprar y los estados llevan la
 * etiqueta en el idioma de la aplicación.
 */
final class StorefrontPresenter
{
    /**
     * One product of the grid of the store.
     *
     * @return array{uuid: string, name: string, slug: string, short_description: string, image: string|null, price_min: float|null, price_max: float|null, in_stock: bool, is_featured: bool, brand: string|null}
     */
    public static function productCard(Product $product): array
    {
        $range = $product->priceRange();

        $brand = $product->relationLoaded('brand') ? $product->brand : null;

        return [
            'uuid' => (string) $product->uuid,
            'name' => (string) $product->name,
            'slug' => (string) $product->slug,
            'short_description' => (string) ($product->short_description ?? ''),
            'image' => self::productImage($product),
            'price_min' => $range === null ? null : (float) $range['min'],
            'price_max' => $range === null ? null : (float) $range['max'],
            'in_stock' => self::inStock($product),
            'is_featured' => (bool) $product->is_featured,
            'brand' => $brand instanceof Brand ? (string) $brand->name : null,
        ];
    }

    /**
     * The product of the page of la ficha.
     *
     * @return array{uuid: string, name: string, slug: string, short_description: string, description: string, images: list<array{url: string, thumbnail_url: string, alt: string, position: int, is_primary: bool}>, variants: list<array<string, mixed>>, brand: array{name: string, slug: string}|null, categories: list<array{name: string, slug: string}>, price_min: float|null, price_max: float|null, in_stock: bool, is_featured: bool}
     */
    public static function productDetail(Product $product): array
    {
        $range = $product->priceRange();

        $brand = $product->relationLoaded('brand') ? $product->brand : null;

        $categories = [];

        foreach ($product->relationLoaded('categories') ? $product->categories : [] as $category) {
            $categories[] = [
                'name' => (string) $category->name,
                'slug' => (string) $category->slug,
            ];
        }

        $variants = [];

        foreach ($product->variants as $variant) {
            $variants[] = self::variant($variant);
        }

        return [
            'uuid' => (string) $product->uuid,
            'name' => (string) $product->name,
            'slug' => (string) $product->slug,
            'short_description' => (string) ($product->short_description ?? ''),
            'description' => (string) ($product->description ?? ''),
            'images' => self::images($product),
            'variants' => $variants,
            'brand' => $brand instanceof Brand
                ? ['name' => (string) $brand->name, 'slug' => (string) $brand->slug]
                : null,
            'categories' => $categories,
            'price_min' => $range === null ? null : (float) $range['min'],
            'price_max' => $range === null ? null : (float) $range['max'],
            'in_stock' => self::inStock($product),
            'is_featured' => (bool) $product->is_featured,
        ];
    }

    /**
     * One variant of a product, as the shopper picks it.
     *
     * The variant is the unit that is sold, so it carries the price, el
     * stock que se puede comprar y el sku.
     *
     * @return array{uuid: string, name: string, sku: string|null, price: float, compare_at_price: float|null, available_stock: int, is_default: bool, image: string|null, options: list<array{id: int, value: string, label: string}>}
     */
    public static function variant(ProductVariant $variant): array
    {
        $options = [];

        foreach ($variant->relationLoaded('optionValues') ? $variant->optionValues : [] as $value) {
            $options[] = [
                'id' => (int) $value->getKey(),
                'value' => (string) $value->value,
                'label' => (string) $value->label,
            ];
        }

        return [
            'uuid' => (string) $variant->uuid,
            'name' => (string) ($variant->name ?? ''),
            'sku' => $variant->sku === null ? null : (string) $variant->sku,
            'price' => (float) $variant->price,
            'compare_at_price' => $variant->compare_at_price === null ? null : (float) $variant->compare_at_price,
            'available_stock' => (int) $variant->availableStock(),
            'is_default' => (bool) $variant->is_default,
            'image' => self::variantImage($variant),
            'options' => $options,
        ];
    }

    /**
     * The gallery of a product.
     *
     * @return list<array{url: string, thumbnail_url: string, alt: string, position: int, is_primary: bool}>
     */
    public static function images(Product $product): array
    {
        if (! $product->relationLoaded('images')) {
            return [];
        }

        $images = [];

        foreach ($product->images as $image) {
            $images[] = self::image($image);
        }

        return $images;
    }

    /**
     * One image of la gallery.
     *
     * @return array{url: string, thumbnail_url: string, alt: string, position: int, is_primary: bool}
     */
    public static function image(ProductImage $image): array
    {
        $thumbnail = Media::firstThumbnailName();

        return [
            'url' => $image->getUrl(),
            'thumbnail_url' => $thumbnail === null || ! $image->hasGeneratedConversion($thumbnail)
                ? $image->getUrl()
                : $image->getUrl($thumbnail),
            'alt' => (string) ($image->alt ?? ''),
            'position' => (int) $image->position,
            'is_primary' => (bool) $image->is_primary,
        ];
    }

    /**
     * One category of the menu of the store.
     *
     * @return array{uuid: string, name: string, slug: string, parent_id: int|null, position: int, is_featured: bool, image: string|null}
     */
    public static function category(Category $category): array
    {
        return [
            'uuid' => (string) $category->uuid,
            'name' => (string) $category->name,
            'slug' => (string) $category->slug,
            'parent_id' => $category->parent_id === null ? null : (int) $category->parent_id,
            'position' => (int) $category->position,
            'is_featured' => (bool) $category->is_featured,
            'image' => self::fileUrl($category->image_path),
        ];
    }

    /**
     * One brand of the store.
     *
     * @return array{id: int, name: string, slug: string, logo: string|null}
     */
    public static function brand(Brand $brand): array
    {
        return [
            'id' => (int) $brand->getKey(),
            'name' => (string) $brand->name,
            'slug' => (string) $brand->slug,
            'logo' => self::fileUrl($brand->logo_path),
        ];
    }

    /**
     * The cart of the shopper.
     *
     * @return array{uuid: string, currency: string, item_count: int, subtotal: float, discount_total: float, tax_total: float, shipping_total: float, total: float, coupon: string|null, items: list<array<string, mixed>>}
     */
    public static function cart(Cart $cart): array
    {
        $items = [];

        foreach ($cart->relationLoaded('items') ? $cart->items : [] as $item) {
            $items[] = self::cartItem($item);
        }

        return [
            'uuid' => (string) $cart->uuid,
            'currency' => (string) $cart->currency,
            'item_count' => (int) $cart->item_count,
            'subtotal' => (float) $cart->subtotal,
            'discount_total' => (float) $cart->discount_total,
            'tax_total' => (float) $cart->tax_total,
            'shipping_total' => (float) $cart->shipping_total,
            'total' => (float) $cart->total,
            'coupon' => $cart->relationLoaded('coupon') && $cart->coupon !== null
                ? (string) $cart->coupon->code
                : null,
            'items' => $items,
        ];
    }

    /**
     * One line of the cart of the shopper.
     *
     * @return array{id: int, name: string, sku: string|null, options: array<string, mixed>, unit_price: float, quantity: int, line_subtotal: float, discount_total: float, tax_total: float, line_total: float}
     */
    public static function cartItem(CartItem $item): array
    {
        return [
            'id' => (int) $item->getKey(),
            'name' => (string) $item->name,
            'sku' => $item->sku === null ? null : (string) $item->sku,
            'options' => $item->options ?? [],
            'unit_price' => (float) $item->unit_price,
            'quantity' => (int) $item->quantity,
            'line_subtotal' => (float) $item->line_subtotal,
            'discount_total' => (float) $item->discount_total,
            'tax_total' => (float) $item->tax_total,
            'line_total' => (float) $item->line_total,
        ];
    }

    /**
     * One order of the shopper.
     *
     * @return array{uuid: string, number: string, status: string, status_label: string, payment_status: string, payment_status_label: string, currency: string, totals: array{subtotal: float, discount: float, tax: float, shipping: float, total: float}, items: list<array<string, mixed>>, buyer: array{name: string, email: string, phone: string}, shipping_address: array<string, mixed>|null, shipping_method: string|null, placed_at: string|null, payment: array{status: string, method_code: string|null, reference: string|null}|null}
     */
    public static function order(Order $order): array
    {
        $items = [];

        foreach ($order->relationLoaded('items') ? $order->items : [] as $item) {
            $items[] = self::orderItem($item);
        }

        $payment = $order->relationLoaded('payments') ? $order->payments->first() : null;

        return [
            'uuid' => (string) $order->uuid,
            'number' => (string) $order->order_number,
            'status' => $order->status->value,
            'status_label' => $order->status->label(),
            'payment_status' => $order->payment_status->value,
            'payment_status_label' => $order->payment_status->label(),
            'currency' => (string) $order->currency,
            'totals' => [
                'subtotal' => (float) $order->subtotal,
                'discount' => (float) $order->discount_total,
                'tax' => (float) $order->tax_total,
                'shipping' => (float) $order->shipping_total,
                'total' => (float) $order->total,
            ],
            'items' => $items,
            'buyer' => [
                'name' => (string) $order->customer_name,
                'email' => (string) ($order->customer_email ?? ''),
                'phone' => (string) ($order->customer_phone ?? ''),
            ],
            'shipping_address' => is_array($order->shipping_address) ? $order->shipping_address : null,
            'shipping_method' => $order->shipping_method_name === null ? null : (string) $order->shipping_method_name,
            'placed_at' => self::date($order->placed_at),
            'payment' => $payment instanceof Payment ? self::payment($payment) : null,
        ];
    }

    /**
     * One item of an order.
     *
     * @return array{name: string, sku: string|null, options: array<string, mixed>, unit_price: float, quantity: int, line_total: float}
     */
    public static function orderItem(OrderItem $item): array
    {
        return [
            'name' => (string) $item->name,
            'sku' => $item->sku === null ? null : (string) $item->sku,
            'options' => $item->variant_options ?? [],
            'unit_price' => (float) $item->unit_price,
            'quantity' => (int) $item->quantity,
            'line_total' => (float) $item->line_total,
        ];
    }

    /**
     * The payment of an order.
     *
     * @return array{status: string, method_code: string|null, reference: string|null}
     */
    public static function payment(Payment $payment): array
    {
        return [
            'status' => $payment->status->value,
            'method_code' => (string) $payment->method_code,
            'reference' => (string) $payment->reference,
        ];
    }

    /**
     * The buyer of the store.
     *
     * @return array{name: string, first_name: string, last_name: string, email: string, phone: string|null, document_type: string|null, document_number: string|null}
     */
    public static function customer(Customer $customer): array
    {
        return [
            'name' => trim($customer->first_name.' '.$customer->last_name),
            'first_name' => (string) $customer->first_name,
            'last_name' => (string) ($customer->last_name ?? ''),
            'email' => (string) $customer->email,
            'phone' => $customer->phone === null ? null : (string) $customer->phone,
            'document_type' => $customer->document_type === null ? null : $customer->document_type->value,
            'document_number' => $customer->document_number === null ? null : (string) $customer->document_number,
        ];
    }

    /**
     * One method of shipping the shopper picks from.
     *
     * @return array{code: string, name: string, type: string, type_label: string, price: float, estimated_days: int|null, instructions: string}
     */
    public static function shippingMethod(ShippingMethod $method): array
    {
        return [
            'code' => (string) $method->code,
            'name' => (string) $method->name,
            'type' => $method->type->value,
            'type_label' => $method->type->label(),
            'price' => (float) $method->price,
            'estimated_days' => $method->estimated_days === null ? null : (int) $method->estimated_days,
            'instructions' => (string) ($method->instructions ?? ''),
        ];
    }

    /**
     * One method of payment the shopper picks from.
     *
     * @return array{code: string, name: string, type: string, type_label: string, instructions: string, icon: string|null}
     */
    public static function paymentMethod(PaymentMethod $method): array
    {
        return [
            'code' => (string) $method->code,
            'name' => (string) $method->name,
            'type' => $method->type->value,
            'type_label' => $method->type->label(),
            'instructions' => (string) ($method->instructions ?? ''),
            'icon' => $method->icon === null ? null : (string) $method->icon,
        ];
    }

    /**
     * A page of a list, with the data the store shows.
     *
     * The items are presented one by one, so the store never receives a model
     * as it is. The shape is the one the store needs: the rows and the
     * numbers of the pagination.
     *
     * @template T of \Illuminate\Database\Eloquent\Model
     *
     * @param  LengthAwarePaginator<int, T>  $paginator
     * @param  callable(T): array<string, mixed>  $present
     * @return array{data: list<array<string, mixed>>, meta: array{current_page: int, last_page: int, per_page: int, total: int, from: int|null, to: int|null}}
     */
    public static function paginate(LengthAwarePaginator $paginator, callable $present): array
    {
        $items = [];

        foreach ($paginator->items() as $item) {
            $items[] = $present($item);
        }

        return [
            'data' => $items,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
        ];
    }

    /**
     * The address of a file of the store.
     */
    public static function fileUrl(?string $path): ?string
    {
        if ($path === null || $path === '') {
            return null;
        }

        try {
            return Storage::disk(Media::disk())->url($path);
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * The picture that represents a product in the grid.
     */
    private static function productImage(Product $product): ?string
    {
        if ($product->relationLoaded('images')) {
            $primary = $product->images->firstWhere('is_primary', true) ?? $product->images->first();

            if ($primary instanceof ProductImage) {
                return self::image($primary)['thumbnail_url'];
            }
        }

        return self::fileUrl($product->thumbnail_path);
    }

    /**
     * The picture of a variant.
     */
    private static function variantImage(ProductVariant $variant): ?string
    {
        $path = $variant->image_path;

        if (! is_string($path) || $path === '') {
            return null;
        }

        return self::fileUrl($path);
    }

    /**
     * Whether a product can be bought right now.
     *
     * The stock que se puede comprar es el que queda después de lo reservado,
     * así que un producto con existencias reservadas no se ofrece.
     */
    private static function inStock(Product $product): bool
    {
        foreach ($product->variants as $variant) {
            if ($variant->availableStock() > 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * A date of the store, in the format the store has always used.
     */
    public static function date(?DateTimeInterface $date): ?string
    {
        return $date?->format('d/m/Y H:i');
    }
}
