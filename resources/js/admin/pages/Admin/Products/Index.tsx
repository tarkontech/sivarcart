import { Head, usePage } from '@inertiajs/react'

import { DeleteButton } from '@/components/DeleteButton'
import { EmptyState } from '@/components/EmptyState'
import { LinkButton } from '@/components/LinkButton'
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
    canUpdate,
}: {
    products: Paginated<ProductListItem>
    filters: { q: string }
    canUpdate: boolean
}) {
    const { nav, text, locale } = usePage<PanelSharedProps>().props

    const base = nav.find((item) => item.key === 'products')?.url ?? window.location.pathname

    return (
        <PanelLayout>
            <Head title={text.products.title} />

            <PageHeader
                title={text.products.title}
                actions={
                    canUpdate ? (
                        <LinkButton href={`${base}/create`} size="sm">
                            {text.products.new}
                        </LinkButton>
                    ) : null
                }
            />

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
                                    {canUpdate ? (
                                        <TableHead>{text.products.actions}</TableHead>
                                    ) : null}
                                </TableRow>
                            </TableHeader>

                            <TableBody>
                                {products.data.map((product) => (
                                    <TableRow key={product.uuid}>
                                        <TableCell>
                                            {canUpdate ? (
                                                <LinkButton
                                                    href={`${base}/${product.uuid}/edit`}
                                                    variant="link"
                                                    size="sm"
                                                    className="h-auto p-0"
                                                >
                                                    {product.name}
                                                </LinkButton>
                                            ) : (
                                                product.name
                                            )}
                                        </TableCell>
                                        <TableCell>{product.sku ?? '—'}</TableCell>
                                        <TableCell>
                                            {moneyRange(product.price_min, product.price_max, locale)}
                                        </TableCell>
                                        <TableCell>{product.stock}</TableCell>
                                        <TableCell>
                                            <StatusBadge state={product.status} />
                                        </TableCell>
                                        {canUpdate ? (
                                            <TableCell>
                                                <div className="flex items-center gap-1">
                                                    <LinkButton
                                                        href={`${base}/${product.uuid}/edit`}
                                                        variant="ghost"
                                                        size="sm"
                                                    >
                                                        {text.products.edit}
                                                    </LinkButton>

                                                    <DeleteButton
                                                        url={`${base}/${product.uuid}`}
                                                        label={text.products.delete_confirm}
                                                        confirm={text.products.delete_confirm}
                                                    />
                                                </div>
                                            </TableCell>
                                        ) : null}
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
