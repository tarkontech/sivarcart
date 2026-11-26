import { Head, usePage } from '@inertiajs/react'

import { DeleteButton } from '@/components/DeleteButton'
import { EmptyState } from '@/components/EmptyState'
import { LinkButton } from '@/components/LinkButton'
import { PageHeader } from '@/components/PageHeader'
import { Pagination } from '@/components/Pagination'
import { SearchInput } from '@/components/SearchInput'
import { Badge } from '@/components/ui/badge'
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
import type { BrandItem, Paginated, PanelSharedProps } from '@/types'

/**
 * The brands of the catalog.
 */
export default function BrandsIndex({
    brands,
    filters,
    canUpdate,
}: {
    brands: Paginated<BrandItem>
    filters: { q: string }
    canUpdate: boolean
}) {
    const { nav, text } = usePage<PanelSharedProps>().props

    const base = nav.find((item) => item.key === 'brands')?.url ?? window.location.pathname

    return (
        <PanelLayout>
            <Head title={text.brands.title} />

            <PageHeader
                title={text.brands.title}
                actions={
                    canUpdate ? (
                        <LinkButton href={`${base}/create`} size="sm">
                            {text.brands.new}
                        </LinkButton>
                    ) : null
                }
            />

            <div className="min-w-64 max-w-md">
                <SearchInput
                    value={filters.q}
                    placeholder={text.brands.search}
                    only={['brands']}
                />
            </div>

            <Card>
                <CardContent className="flex flex-col gap-4">
                    {brands.data.length === 0 ? (
                        <EmptyState>{text.brands.empty}</EmptyState>
                    ) : (
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>{text.brands.name}</TableHead>
                                    <TableHead>{text.brands.products}</TableHead>
                                    <TableHead>{text.brands.active}</TableHead>
                                    {canUpdate ? (
                                        <TableHead>{text.brands.actions}</TableHead>
                                    ) : null}
                                </TableRow>
                            </TableHeader>

                            <TableBody>
                                {brands.data.map((brand) => (
                                    <TableRow key={brand.id}>
                                        <TableCell>
                                            {canUpdate ? (
                                                <LinkButton
                                                    href={`${base}/${brand.id}/edit`}
                                                    variant="link"
                                                    size="sm"
                                                    className="h-auto p-0"
                                                >
                                                    {brand.name}
                                                </LinkButton>
                                            ) : (
                                                brand.name
                                            )}
                                        </TableCell>
                                        <TableCell>{brand.products}</TableCell>
                                        <TableCell>
                                            <Badge tone={brand.is_active ? 'success' : 'danger'}>
                                                {brand.is_active ? '✓' : '—'}
                                            </Badge>
                                        </TableCell>
                                        {canUpdate ? (
                                            <TableCell>
                                                <div className="flex items-center gap-1">
                                                    <LinkButton
                                                        href={`${base}/${brand.id}/edit`}
                                                        variant="ghost"
                                                        size="sm"
                                                    >
                                                        {text.brands.edit}
                                                    </LinkButton>

                                                    <DeleteButton
                                                        url={`${base}/${brand.id}`}
                                                        label={text.brands.delete_confirm}
                                                        confirm={text.brands.delete_confirm}
                                                    />
                                                </div>
                                            </TableCell>
                                        ) : null}
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    )}

                    <Pagination meta={brands.meta} only={['brands']} />
                </CardContent>
            </Card>
        </PanelLayout>
    )
}
