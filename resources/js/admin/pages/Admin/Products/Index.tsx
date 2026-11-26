import { Head, usePage } from '@inertiajs/react'

import { EmptyState } from '@/components/EmptyState'
import { PageHeader } from '@/components/PageHeader'
import { Pagination } from '@/components/Pagination'
import { SearchInput } from '@/components/SearchInput'
import { StatusBadge } from '@/components/StatusBadge'
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
import { moneyRange } from '@/lib/format'
import type { Paginated, PanelSharedProps, ProductListItem } from '@/types'

/**
 * The catalog of the store.
 *
 * Unlike the catalog of the store, this one shows the drafts too: the staff
 * works on a product before it is published.
 */
export default function ProductsIndex({
    products,
    filters,
}: {
    products: Paginated<ProductListItem>
    filters: { q: string }
}) {
    const { text, locale } = usePage<PanelSharedProps>().props

    return (
        <PanelLayout>
            <Head title={text.products.title} />

            <PageHeader title={text.products.title} />

            <div className="min-w-64 max-w-md">
                <SearchInput
                    value={filters.q}
                    placeholder={text.products.search}
                    only={['products']}
                />
            </div>

            <Card>
                <CardContent className="flex flex-col gap-4">
                    {products.data.length === 0 ? (
                        <EmptyState>{text.products.empty}</EmptyState>
                    ) : (
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>{text.products.name}</TableHead>
                                    <TableHead>{text.products.sku}</TableHead>
                                    <TableHead>{text.products.price}</TableHead>
                                    <TableHead>{text.products.stock}</TableHead>
                                    <TableHead>{text.products.status}</TableHead>
                                </TableRow>
                            </TableHeader>

                            <TableBody>
                                {products.data.map((product) => (
                                    <TableRow key={product.uuid}>
                                        <TableCell>{product.name}</TableCell>
                                        <TableCell>{product.sku ?? '—'}</TableCell>
                                        <TableCell>
                                            {moneyRange(product.price_min, product.price_max, locale)}
                                        </TableCell>
                                        <TableCell>{product.stock}</TableCell>
                                        <TableCell>
                                            <StatusBadge state={product.status} />
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    )}

                    <Pagination meta={products.meta} only={['products']} />
                </CardContent>
            </Card>
        </PanelLayout>
    )
}
