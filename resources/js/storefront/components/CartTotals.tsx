import { moneyWithCurrency } from '@storefront/lib/format'
import type { Totals } from '@storefront/types'

/**
 * The totals of a cart or of an order.
 *
 * Los totales los calcula el servidor: el cliente solo los muestra. The
 * shopper never sees a total that the server did not write.
 */
export function CartTotals({
    totals,
    currency,
    locale,
    labels,
}: {
    totals: Totals
    currency: string
    locale: string
    labels: {
        subtotal: string
        discount: string
        tax: string
        shipping: string
        grand_total: string
    }
}) {
    const rows = [
        { label: labels.subtotal, value: totals.subtotal },
        { label: labels.discount, value: totals.discount },
        { label: labels.tax, value: totals.tax },
        { label: labels.shipping, value: totals.shipping },
    ]

    return (
        <dl className="flex flex-col gap-2 text-sm">
            {rows.map((row) => (
                <div key={row.label} className="flex items-center justify-between gap-4">
                    <dt className="text-muted-foreground">{row.label}</dt>
                    <dd>{moneyWithCurrency(row.value, currency, locale)}</dd>
                </div>
            ))}

            <div className="mt-2 flex items-center justify-between gap-4 border-t border-border pt-2 text-base font-semibold">
                <dt>{labels.grand_total}</dt>
                <dd className="text-primary">
                    {moneyWithCurrency(totals.total, currency, locale)}
                </dd>
            </div>
        </dl>
    )
}
