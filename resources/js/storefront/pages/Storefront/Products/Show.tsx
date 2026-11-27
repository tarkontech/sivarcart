import { Head, Link, router, usePage } from '@inertiajs/react'
import { useState } from 'react'

import { Button } from '@storefront/components/ui/button'
import { Input } from '@storefront/components/ui/input'
import { Label } from '@storefront/components/ui/label'
import { Separator } from '@storefront/components/ui/separator'
import { moneyWithCurrency } from '@storefront/lib/format'
import { storePath } from '@storefront/lib/url'
import { StorefrontLayout } from '@storefront/layouts/StorefrontLayout'
import type { ProductDetail, StorefrontSharedProps } from '@storefront/types'

/**
 * The page of a product.
 *
 * The variant es lo que se vende, así que el comprador elige una variante y
 * la pone en el carrito. Los precios y el stock los escribió el servidor.
 */
export default function ProductShow({ product }: { product: ProductDetail }) {
    const { store, prefix, locale, text } = usePage<StorefrontSharedProps>().props
    const [variant, setVariant] = useState(
        product.variants.find((item) => item.is_default) ?? product.variants[0],
    )
    const [quantity, setQuantity] = useState(1)

    const errors = usePage<StorefrontSharedProps>().props.errors as
        | Record<string, string>
        | undefined

    const addToCart = () => {
        if (!variant) {
            return
        }

        router.post(
            storePath(prefix, 'carrito/items'),
            { variant_id: variant.uuid, quantity: String(quantity) },
            { preserveScroll: true },
        )
    }

    return (
        <StorefrontLayout>
            <Head title={product.name} />

            <div className="flex flex-col gap-6">
                <Link
                    href={storePath(prefix, 'productos')}
                    className="text-sm text-muted-foreground hover:text-foreground"
                >
                    ← {text.product.back}
                </Link>

                <div className="grid gap-8 lg:grid-cols-2">
                    <div className="flex flex-col gap-3">
                        {product.images.length > 0 ? (
                            <img
                                src={product.images[0].url}
                                alt={product.images[0].alt || product.name}
                                className="aspect-square w-full rounded-xl border border-border bg-muted object-cover"
                            />
                        ) : (
                            <div className="flex aspect-square w-full items-center justify-center rounded-xl border border-dashed border-border bg-muted text-muted-foreground">
                                —
                            </div>
                        )}

                        {product.images.length > 1 ? (
                            <div className="flex flex-wrap gap-2">
                                {product.images.map((image) => (
                                    <img
                                        key={image.url}
                                        src={image.thumbnail_url}
                                        alt={image.alt || product.name}
                                        className="size-16 rounded-lg border border-border object-cover"
                                    />
                                ))}
                            </div>
                        ) : null}
                    </div>

                    <div className="flex flex-col gap-4">
                        <div className="flex flex-col gap-1">
                            {product.brand ? (
                                <span className="text-sm text-muted-foreground">
                                    {product.brand.name}
                                </span>
                            ) : null}

                            <h1 className="text-3xl font-semibold tracking-tight">
                                {product.name}
                            </h1>

                            {product.short_description ? (
                                <p className="text-muted-foreground">
                                    {product.short_description}
                                </p>
                            ) : null}
                        </div>

                        <span className="text-2xl font-semibold text-primary">
                            {variant
                                ? moneyWithCurrency(variant.price, store.currency, locale)
                                : moneyWithCurrency(
                                      product.price_min ?? 0,
                                      store.currency,
                                      locale,
                                  )}
                        </span>

                        {product.variants.length > 1 ? (
                            <div className="flex flex-col gap-2">
                                <Label>{text.product.choose_variant}</Label>

                                <div className="flex flex-wrap gap-2">
                                    {product.variants.map((item) => (
                                        <Button
                                            key={item.uuid}
                                            type="button"
                                            variant={
                                                variant?.uuid === item.uuid ? 'default' : 'outline'
                                            }
                                            size="sm"
                                            onClick={() => setVariant(item)}
                                        >
                                            {item.name || item.sku || text.product.variant}
                                        </Button>
                                    ))}
                                </div>
                            </div>
                        ) : null}

                        {variant ? (
                            <div className="flex flex-col gap-1 text-sm text-muted-foreground">
                                {variant.sku ? (
                                    <span>
                                        {text.product.sku}: {variant.sku}
                                    </span>
                                ) : null}

                                <span>
                                    {variant.available_stock > 0
                                        ? `${text.product.only_left}: ${variant.available_stock}`
                                        : text.product.out_of_stock}
                                </span>
                            </div>
                        ) : null}

                        <div className="flex items-end gap-3">
                            <div className="flex flex-col gap-2">
                                <Label htmlFor="quantity">{text.product.quantity}</Label>

                                <Input
                                    id="quantity"
                                    type="number"
                                    min="1"
                                    max="9999"
                                    value={quantity}
                                    onChange={(event) =>
                                        setQuantity(Math.max(1, Number(event.target.value) || 1))
                                    }
                                    className="w-24"
                                />
                            </div>

                            <Button
                                type="button"
                                onClick={addToCart}
                                disabled={!variant || variant.available_stock <= 0}
                            >
                                {text.product.add_to_cart}
                            </Button>
                        </div>

                        {errors?.variant_id ? (
                            <p className="text-sm text-destructive">{errors.variant_id}</p>
                        ) : null}

                        {product.description ? (
                            <>
                                <Separator />

                                <div className="flex flex-col gap-2">
                                    <h2 className="text-lg font-semibold">
                                        {text.product.description}
                                    </h2>

                                    <p className="whitespace-pre-line text-sm text-muted-foreground">
                                        {product.description}
                                    </p>
                                </div>
                            </>
                        ) : null}

                        {product.categories.length > 0 ? (
                            <div className="flex flex-wrap items-center gap-2">
                                <span className="text-sm text-muted-foreground">
                                    {text.product.categories}:
                                </span>

                                {product.categories.map((category) => (
                                    <span
                                        key={category.slug}
                                        className="rounded-full border border-border px-2 py-0.5 text-xs"
                                    >
                                        {category.name}
                                    </span>
                                ))}
                            </div>
                        ) : null}
                    </div>
                </div>
            </div>
        </StorefrontLayout>
    )
}
