import { Head, Link, router, usePage } from '@inertiajs/react'

import { CartTotals } from '@storefront/components/CartTotals'
import { Button } from '@storefront/components/ui/button'
import { Separator } from '@storefront/components/ui/separator'
import { moneyWithCurrency } from '@storefront/lib/format'
import { storePath } from '@storefront/lib/url'
import { StorefrontLayout } from '@storefront/layouts/StorefrontLayout'
import type { OrderItemData, StorefrontSharedProps } from '@storefront/types'

/**
 * The page of an order.
 *
 * It is the screen de confirmación cuando la orden se acaba de colocar, y la
 * pantalla de la orden cuando el comprador vuelve a buscarla.
 */
export default function OrdersShow({ order }: { order: OrderItemData }) {
    const { prefix, locale, text } = usePage<StorefrontSharedProps>().props

    const flash = usePage<StorefrontSharedProps>().flash as
        | {
              payment_link?: { url: string; reference: string } | null
              payment_link_error?: string | null
          }
        | undefined

    const errors = usePage<StorefrontSharedProps>().props.errors as
        | Record<string, string>
        | undefined

    const link = flash?.payment_link ?? null
    const failure = flash?.payment_link_error ?? errors?.payment ?? null

    return (
        <StorefrontLayout>
            <Head title={text.orders.number + ' ' + order.number} />

            <div className="flex flex-col gap-6">
                <Link
                    href={storePath(prefix, 'pedidos')}
                    className="text-sm text-muted-foreground hover:text-foreground"
                >
                    ← {text.orders.back}
                </Link>

                <section className="flex flex-col gap-2 rounded-xl border border-border bg-card p-6">
                    <h1 className="text-2xl font-semibold tracking-tight">
                        {text.orders.thanks}
                    </h1>

                    <p className="text-muted-foreground">{text.orders.thanks_body}</p>

                    <p className="pt-2 text-sm text-muted-foreground">
                        {text.orders.number}:{' '}
                        <span className="font-medium text-foreground">{order.number}</span>
                    </p>
                </section>

                {link ? (
                    <section className="flex flex-col gap-3 rounded-lg border border-border bg-card p-4">
                        <span className="text-sm font-medium">
                            {text.orders.payment_link}
                        </span>

                        <a href={link.url} target="_blank" rel="noopener noreferrer">
                            <Button>{text.orders.pay_now}</Button>
                        </a>
                    </section>
                ) : null}

                {failure ? (
                    <section className="flex flex-col gap-3 rounded-lg border border-destructive/40 bg-card p-4">
                        <span className="text-sm text-destructive">{failure}</span>

                        <Button
                            type="button"
                            variant="outline"
                            onClick={() =>
                                router.post(
                                    storePath(prefix, `pedidos/${order.uuid}/pagar`),
                                    {},
                                    { preserveScroll: true },
                                )
                            }
                        >
                            {text.orders.pay_now}
                        </Button>
                    </section>
                ) : null}

                <div className="grid gap-8 lg:grid-cols-[1fr_20rem]">
                    <section className="flex flex-col gap-4 rounded-lg border border-border bg-card p-4">
                        <h2 className="text-lg font-semibold">{text.orders.items}</h2>

                        <div className="flex flex-col gap-3">
                            {order.items.map((item, index) => (
                                <div
                                    key={`${item.name}-${index}`}
                                    className="flex items-start justify-between gap-4"
                                >
                                    <span className="text-sm">
                                        {item.quantity} × {item.name}
                                    </span>

                                    <span className="text-sm font-medium">
                                        {moneyWithCurrency(
                                            item.line_total,
                                            order.currency,
                                            locale,
                                        )}
                                    </span>
                                </div>
                            ))}
                        </div>

                        <Separator />

                        <div className="flex flex-col gap-1 text-sm">
                            <span className="text-muted-foreground">
                                {text.orders.status}: {order.status_label}
                            </span>

                            <span className="text-muted-foreground">
                                {text.orders.payment}: {order.payment_status_label}
                            </span>

                            {order.shipping_method ? (
                                <span className="text-muted-foreground">
                                    {order.shipping_method}
                                </span>
                            ) : null}
                        </div>
                    </section>

                    <aside className="flex h-fit flex-col gap-4 rounded-lg border border-border bg-card p-4">
                        <h2 className="text-lg font-semibold">{text.checkout.summary}</h2>

                        <CartTotals
                            totals={order.totals}
                            currency={order.currency}
                            locale={locale}
                            labels={{
                                subtotal: text.cart.subtotal,
                                discount: text.cart.discount,
                                tax: text.cart.tax,
                                shipping: text.cart.shipping,
                                grand_total: text.cart.grand_total,
                            }}
                        />

                        {order.shipping_address ? (
                            <>
                                <Separator />

                                <div className="flex flex-col gap-1 text-sm text-muted-foreground">
                                    {Object.entries(order.shipping_address).map(
                                        ([key, value]) =>
                                            typeof value === 'string' && value !== '' ? (
                                                <span key={key}>{value}</span>
                                            ) : null,
                                    )}
                                </div>
                            </>
                        ) : null}
                    </aside>
                </div>
            </div>
        </StorefrontLayout>
    )
}
