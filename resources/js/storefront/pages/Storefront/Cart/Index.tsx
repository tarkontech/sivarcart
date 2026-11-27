import { Head, Link, router, usePage } from '@inertiajs/react'

import { CartTotals } from '@storefront/components/CartTotals'
import { EmptyState } from '@storefront/components/EmptyState'
import { LinkButton } from '@storefront/components/LinkButton'
import { Button } from '@storefront/components/ui/button'
import { Input } from '@storefront/components/ui/input'
import { Label } from '@storefront/components/ui/label'
import { Separator } from '@storefront/components/ui/separator'
import { moneyWithCurrency } from '@storefront/lib/format'
import { storePath } from '@storefront/lib/url'
import { StorefrontLayout } from '@storefront/layouts/StorefrontLayout'
import type { CartData, StorefrontSharedProps } from '@storefront/types'

/**
 * The cart of the shopper.
 *
 * The lines del carrito y sus totales los escribió el servidor: el cliente
 * solo los muestra y los manda a cambiar.
 */
export default function CartIndex({ cart }: { cart: CartData }) {
    const { store, prefix, locale, text } = usePage<StorefrontSharedProps>().props

    const errors = usePage<StorefrontSharedProps>().props.errors as
        | Record<string, string>
        | undefined

    const changeQuantity = (id: number, quantity: number) => {
        router.patch(
            storePath(prefix, `carrito/items/${id}`),
            { quantity: String(quantity) },
            { preserveScroll: true },
        )
    }

    const remove = (id: number) => {
        router.delete(storePath(prefix, `carrito/items/${id}`), {
            preserveScroll: true,
        })
    }

    return (
        <StorefrontLayout>
            <Head title={text.cart.title} />

            <div className="flex flex-col gap-6">
                <h1 className="text-2xl font-semibold tracking-tight">{text.cart.title}</h1>

                {cart.items.length === 0 ? (
                    <div className="flex flex-col items-center gap-4">
                        <EmptyState>{text.cart.empty}</EmptyState>

                        <p className="text-sm text-muted-foreground">{text.cart.empty_hint}</p>

                        <LinkButton href={storePath(prefix, 'productos')}>
                            {text.cart.continue}
                        </LinkButton>
                    </div>
                ) : (
                    <div className="grid gap-8 lg:grid-cols-[1fr_20rem]">
                        <div className="flex flex-col gap-4">
                            {cart.items.map((line) => (
                                <div
                                    key={line.id}
                                    className="flex items-start justify-between gap-4 rounded-lg border border-border bg-card p-4"
                                >
                                    <div className="flex min-w-0 flex-col gap-1">
                                        <span className="font-medium">{line.name}</span>

                                        {line.sku ? (
                                            <span className="text-xs text-muted-foreground">
                                                {text.cart.unit}: {line.sku}
                                            </span>
                                        ) : null}

                                        <span className="text-sm text-muted-foreground">
                                            {moneyWithCurrency(
                                                line.unit_price,
                                                store.currency,
                                                locale,
                                            )}
                                        </span>
                                    </div>

                                    <div className="flex shrink-0 flex-col items-end gap-2">
                                        <Input
                                            type="number"
                                            min="1"
                                            max="9999"
                                            value={line.quantity}
                                            onChange={(event) =>
                                                changeQuantity(
                                                    line.id,
                                                    Math.max(
                                                        1,
                                                        Number(event.target.value) || 1,
                                                    ),
                                                )
                                            }
                                            className="w-24"
                                            aria-label={text.cart.quantity}
                                        />

                                        <span className="font-semibold">
                                            {moneyWithCurrency(
                                                line.line_total,
                                                store.currency,
                                                locale,
                                            )}
                                        </span>

                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="sm"
                                            onClick={() => remove(line.id)}
                                        >
                                            {text.cart.remove}
                                        </Button>
                                    </div>
                                </div>
                            ))}

                            <div className="flex flex-col gap-2 rounded-lg border border-border bg-card p-4">
                                <Label htmlFor="code">{text.cart.coupon}</Label>

                                <form
                                    className="flex items-start gap-2"
                                    onSubmit={(event) => {
                                        event.preventDefault()

                                        const input = document.getElementById(
                                            'code',
                                        ) as HTMLInputElement | null

                                        if (!input || input.value === '') {
                                            return
                                        }

                                        router.post(
                                            storePath(prefix, 'carrito/cupon'),
                                            { code: input.value },
                                            { preserveScroll: true },
                                        )
                                    }}
                                >
                                    <Input
                                        id="code"
                                        placeholder={text.cart.coupon_placeholder}
                                    />

                                    <Button type="submit" variant="outline">
                                        {text.cart.apply}
                                    </Button>
                                </form>

                                {errors?.code ? (
                                    <p className="text-sm text-destructive">{errors.code}</p>
                                ) : null}

                                {cart.coupon ? (
                                    <div className="flex items-center justify-between gap-2 text-sm">
                                        <span>
                                            {text.cart.coupon}:{' '}
                                            <span className="font-medium">{cart.coupon}</span>
                                        </span>

                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="sm"
                                            onClick={() =>
                                                router.delete(
                                                    storePath(prefix, 'carrito/cupon'),
                                                    { preserveScroll: true },
                                                )
                                            }
                                        >
                                            {text.cart.remove_coupon}
                                        </Button>
                                    </div>
                                ) : null}
                            </div>

                            <div className="flex flex-wrap items-center justify-between gap-3">
                                <LinkButton
                                    href={storePath(prefix, 'productos')}
                                    variant="outline"
                                >
                                    {text.cart.continue}
                                </LinkButton>

                                <Button
                                    type="button"
                                    variant="ghost"
                                    onClick={() =>
                                        router.delete(storePath(prefix, 'carrito'), {
                                            preserveScroll: true,
                                        })
                                    }
                                >
                                    {text.cart.clear}
                                </Button>
                            </div>
                        </div>

                        <aside className="flex h-fit flex-col gap-4 rounded-lg border border-border bg-card p-4">
                            <h2 className="text-lg font-semibold">{text.cart.total}</h2>

                            <CartTotals
                                totals={{
                                    subtotal: cart.subtotal,
                                    discount: cart.discount_total,
                                    tax: cart.tax_total,
                                    shipping: cart.shipping_total,
                                    total: cart.total,
                                }}
                                currency={store.currency}
                                locale={locale}
                                labels={{
                                    subtotal: text.cart.subtotal,
                                    discount: text.cart.discount,
                                    tax: text.cart.tax,
                                    shipping: text.cart.shipping,
                                    grand_total: text.cart.grand_total,
                                }}
                            />

                            <Separator />

                            <LinkButton href={storePath(prefix, 'checkout')}>
                                {text.cart.checkout}
                            </LinkButton>

                            <Link
                                href={storePath(prefix, 'productos')}
                                className="text-center text-sm text-muted-foreground hover:text-foreground hover:underline"
                            >
                                {text.cart.continue}
                            </Link>
                        </aside>
                    </div>
                )}
            </div>
        </StorefrontLayout>
    )
}
