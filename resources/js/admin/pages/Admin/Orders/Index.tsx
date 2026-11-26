import { Head, Link, router, usePage } from '@inertiajs/react'

import { EmptyState } from '@/components/EmptyState'
import { PageHeader } from '@/components/PageHeader'
import { Pagination } from '@/components/Pagination'
import { SearchInput } from '@/components/SearchInput'
import { StatusBadge } from '@/components/StatusBadge'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Card, CardContent } from '@/components/ui/card'
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table'
import { PanelLayout } from '@/layouts/PanelLayout'
import { currentQuery } from '@/lib/query'
import { moneyWithCurrency } from '@/lib/format'
import type { OrderListItem, Paginated, PanelSharedProps, State } from '@/types'

/**
 * The orders of the store.
 *
 * The staff of the store searches the orders by number or by buyer and
 * filters them by the state of their lifecycle. Both travel in the address of
 * the page, so the list can be shared and reloaded.
 */
export default function OrdersIndex({
    orders,
    statuses,
    filters,
}: {
    orders: Paginated<OrderListItem>
    statuses: State[]
    filters: { q: string; status: string }
}) {
    const { text, locale } = usePage<PanelSharedProps>().props

    const changeStatus = (status: string) => {
        router.get(
            window.location.pathname,
            { ...currentQuery(), estado: status === '' ? undefined : status, page: undefined },
            { preserveState: true, preserveScroll: true, only: ['orders'] },
        )
    }

    return (
        <PanelLayout>
            <Head title={text.orders.title} />

            <PageHeader title={text.orders.title} />

            <div className="flex flex-wrap items-center gap-3">
                <div className="min-w-64 flex-1">
                    <SearchInput
                        value={filters.q}
                        placeholder={text.orders.search}
                        only={['orders']}
                    />
                </div>

                <Select
                    items={statuses}
                    value={filters.status === '' ? null : filters.status}
                    onValueChange={(value) => changeStatus(value ?? '')}
                >
                    <SelectTrigger aria-label={text.orders.all_statuses} className="w-56">
                        <SelectValue placeholder={text.orders.all_statuses} />
                    </SelectTrigger>

                    <SelectContent>
                        <SelectItem value="">{text.orders.all_statuses}</SelectItem>
                        {statuses.map((status) => (
                            <SelectItem key={status.value} value={status.value}>
                                {status.label}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
            </div>

            <Card>
                <CardContent className="flex flex-col gap-4">
                    {orders.data.length === 0 ? (
                        <EmptyState>{text.orders.empty}</EmptyState>
                    ) : (
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>{text.orders.number}</TableHead>
                                    <TableHead>{text.orders.customer}</TableHead>
                                    <TableHead>{text.orders.date}</TableHead>
                                    <TableHead>{text.orders.total}</TableHead>
                                    <TableHead>{text.orders.status}</TableHead>
                                    <TableHead>{text.orders.payment}</TableHead>
                                </TableRow>
                            </TableHeader>

                            <TableBody>
                                {orders.data.map((order) => (
                                    <TableRow key={order.uuid}>
                                        <TableCell>
                                            <Link
                                                href={`/sivarcart/admin/orders/${order.uuid}`}
                                                className="font-medium text-primary underline-offset-4 hover:underline"
                                            >
                                                {order.number}
                                            </Link>
                                        </TableCell>
                                        <TableCell>{order.customer}</TableCell>
                                        <TableCell>{order.date ?? '—'}</TableCell>
                                        <TableCell>
                                            {moneyWithCurrency(order.total, order.currency, locale)}
                                        </TableCell>
                                        <TableCell>
                                            <StatusBadge state={order.status} />
                                        </TableCell>
                                        <TableCell>
                                            <StatusBadge state={order.payment} />
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    )}

                    <Pagination meta={orders.meta} only={['orders']} />
                </CardContent>
            </Card>
        </PanelLayout>
    )
}
