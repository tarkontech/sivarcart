import { Head, usePage } from '@inertiajs/react'

import { EmptyState } from '@/components/EmptyState'
import { PageHeader } from '@/components/PageHeader'
import { Pagination } from '@/components/Pagination'
import { SearchInput } from '@/components/SearchInput'
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
import type { CustomerListItem, Paginated, PanelSharedProps } from '@/types'

/**
 * The buyers of the store.
 *
 * The staff of the store looks for a buyer by name, by email or by phone.
 */
export default function CustomersIndex({
    customers,
    filters,
}: {
    customers: Paginated<CustomerListItem>
    filters: { q: string }
}) {
    const { text } = usePage<PanelSharedProps>().props

    return (
        <PanelLayout>
            <Head title={text.customers.title} />

            <PageHeader title={text.customers.title} />

            <div className="min-w-64 max-w-md">
                <SearchInput
                    value={filters.q}
                    placeholder={text.customers.search}
                    only={['customers']}
                />
            </div>

            <Card>
                <CardContent className="flex flex-col gap-4">
                    {customers.data.length === 0 ? (
                        <EmptyState>{text.customers.empty}</EmptyState>
                    ) : (
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>{text.customers.name}</TableHead>
                                    <TableHead>{text.customers.email}</TableHead>
                                    <TableHead>{text.customers.phone}</TableHead>
                                    <TableHead>{text.customers.orders}</TableHead>
                                </TableRow>
                            </TableHeader>

                            <TableBody>
                                {customers.data.map((customer) => (
                                    <TableRow key={customer.email}>
                                        <TableCell>{customer.name || '—'}</TableCell>
                                        <TableCell>{customer.email || '—'}</TableCell>
                                        <TableCell>{customer.phone || '—'}</TableCell>
                                        <TableCell>{customer.orders}</TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    )}

                    <Pagination meta={customers.meta} only={['customers']} />
                </CardContent>
            </Card>
        </PanelLayout>
    )
}
