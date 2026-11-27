import { Link, usePage } from '@inertiajs/react'

import { cn } from '@storefront/lib/utils'
import { moneyRange } from '@storefront/lib/format'
import { storePath } from '@storefront/lib/url'
import type { ProductCard as ProductCardData, StorefrontSharedProps } from '@storefront/types'

/**
 * One product of the grid of the store.
 *
 * The card shows el nombre, la imagen y el precio. El precio de un producto
 * es el de sus variantes, así que la tarjeta muestra el rango cuando el
 * producto tiene más de un precio.
 */
export function ProductCard({
    product,
    currency,
    locale,
    className,
}: {
    product: ProductCardData
    currency: string
    locale: string
    className?: string
}) {
    const { prefix } = usePage<StorefrontSharedProps>().props

    const price = moneyRange(product.price_min, product.price_max, locale)

    return (
        <Link
            href={storePath(prefix, `productos/${product.uuid}`)}
            className={cn(
                'group flex flex-col overflow-hidden rounded-lg border border-border bg-card transition-colors hover:border-primary',
                className,
            )}
        >
            <div className="aspect-square overflow-hidden bg-muted">
                {product.image ? (
                    <img
                        src={product.image}
                        alt={product.name}
                        loading="lazy"
                        className="size-full object-cover transition-transform group-hover:scale-105"
                    />
                ) : (
                    <div className="flex size-full items-center justify-center text-sm text-muted-foreground">
                        —
                    </div>
                )}
            </div>

            <div className="flex flex-1 flex-col gap-1 p-4">
                {product.brand ? (
                    <span className="text-xs text-muted-foreground">{product.brand}</span>
                ) : null}

                <h3 className="line-clamp-2 text-sm font-medium leading-snug">
                    {product.name}
                </h3>

                {product.short_description ? (
                    <p className="line-clamp-2 text-xs text-muted-foreground">
                        {product.short_description}
                    </p>
                ) : null}

                <div className="mt-auto flex items-center justify-between gap-2 pt-2">
                    <span className="text-base font-semibold text-primary">
                        {price} {currency}
                    </span>

                    {!product.in_stock ? (
                        <span className="text-xs text-muted-foreground">Agotado</span>
                    ) : null}
                </div>
            </div>
        </Link>
    )
}
