import { Head, usePage } from '@inertiajs/react'

import { PageHeader } from '@/components/PageHeader'
import { StatCard } from '@/components/StatCard'
import { WidgetSlot } from '@/components/WidgetSlot'
import { PanelLayout } from '@/layouts/PanelLayout'
import type { PanelSharedProps, Widget } from '@/types'

/**
 * The home of the panel.
 *
 * It shows the counters the staff of the store looks at first, and the
 * widgets the registry of the panel knows.
 */
export default function Dashboard({
    ordersToday,
    ordersAwaiting,
    ordersUnpaid,
    products,
    lowStock,
    widgets,
}: {
    ordersToday: number
    ordersAwaiting: number
    ordersUnpaid: number
    products: number
    lowStock: number
    widgets: Widget[]
}) {
    const { text } = usePage<PanelSharedProps>().props

    return (
        <PanelLayout>
            <Head title={text.dashboard.title} />

            <PageHeader title={text.dashboard.title} description={text.dashboard.description} />

            <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
                <StatCard label={text.dashboard.orders_today} value={ordersToday} />
                <StatCard label={text.dashboard.orders_awaiting} value={ordersAwaiting} />
                <StatCard label={text.dashboard.orders_unpaid} value={ordersUnpaid} />
                <StatCard label={text.dashboard.products} value={products} />
                <StatCard label={text.dashboard.low_stock} value={lowStock} />
            </div>

            {widgets.length > 0 ? (
                <div className="grid gap-4 md:grid-cols-2">
                    {widgets.map((widget) => (
                        <WidgetSlot key={widget.key} name={widget.component} props={widget.props} />
                    ))}
                </div>
            ) : null}
        </PanelLayout>
    )
}
