import { Head, usePage } from '@inertiajs/react'

import { EmptyState } from '@storefront/components/EmptyState'
import { Pagination } from '@storefront/components/Pagination'
import { LinkButton } from '@storefront/components/LinkButton'
import { moneyWithCurrency } from '@storefront/lib/format'
import { storePath } from '@storefront/lib/url'
import { StorefrontLayout } from '@storefront/layouts/StorefrontLayout'
import type {
    OrderItemData,
    Paginated,
    StorefrontSharedProps,
} from '@storefront/types'

/**
 * The orders of the shopper.
 */
export default function OrdersIndex({
    orders,
}: {
    orders: Paginated<OrderItemData>
}) {
    const { prefix, locale, text } = usePage<StorefrontSharedProps>().props

    return (
        <StorefrontLayout>
            <Head title={text.orders.title} />

            <div className="flex flex-col gap-6">
                <h1 className="text-2xl font-semibold tracking-tight">{text.orders.title}</h1>

                {orders.data.length === 0 ? (
                    <EmptyState>{text.orders.empty}</EmptyState>
                ) : (
                    <>
                        <div className="flex flex-col gap-3">
                            {orders.data.map((order) => (
                                <div
                                    key={order.uuid}
                                    className="flex flex-wrap items-center justify-between gap-4 rounded-lg border border-border bg-card p-4"
                                >
                                    <div className="flex flex-col gap-1">
                                        <span className="font-medium">
                                            {text.orders.number}: {order.number}
                                        </span>

                                        <span className="text-sm text-muted-foreground">
                                            {order.placed_at} · {order.status_label} ·{' '}
                                            {order.payment_status_label}
                                        </span>

                                        <span className="text-sm text-muted-foreground">
                                            {order.items.length} {text.orders.items}
                                        </span>
                                    </div>

                                    <div className="flex items-center gap-4">
                                        <span className="font-semibold">
                                            {moneyWithCurrency(
                                                order.totals.total,
                                                order.currency,
                                                locale,
                                            )}
                                        </span>

                                        <LinkButton
                                            href={storePath(prefix, `pedidos/${order.uuid}`)}
                                            variant="outline"
                                            size="sm"
                                        >
                                            {text.orders.view}
                                        </LinkButton>
                                    </div>
                                </div>
                            ))}
                        </div>

                        <Pagination
                            meta={orders.meta}
                            only={['orders']}
                            previous={text.catalog.previous}
                            next={text.catalog.next}
                        />
                    </>
                )}
            </div>
        </StorefrontLayout>
    )
}
