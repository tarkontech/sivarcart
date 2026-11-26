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
import { money } from '@/lib/format'
import type { Paginated, PanelSharedProps, ShippingMethodItem } from '@/types'

/**
 * The shipping methods of the store.
 *
 * A method that the store is still preparing is not active yet, so the panel
 * shows every method and not only the ones a shopper can choose.
 */
export default function ShippingMethodsIndex({
    methods,
    filters,
    canUpdate,
}: {
    methods: Paginated<ShippingMethodItem>
    filters: { q: string }
    canUpdate: boolean
}) {
    const { nav, text, locale } = usePage<PanelSharedProps>().props

    const base =
        nav.find((item) => item.key === 'shipping-methods')?.url ?? window.location.pathname

    return (
        <PanelLayout>
            <Head title={text.shipping_methods.title} />

            <PageHeader
                title={text.shipping_methods.title}
                actions={
                    canUpdate ? (
                        <LinkButton href={`${base}/create`} size="sm">
                            {text.shipping_methods.new}
                        </LinkButton>
                    ) : null
                }
            />

            <div className="min-w-64 max-w-md">
                <SearchInput
                    value={filters.q}
                    placeholder={text.shipping_methods.search}
                    only={['methods']}
                />
            </div>

            <Card>
                <CardContent className="flex flex-col gap-4">
                    {methods.data.length === 0 ? (
                        <EmptyState>{text.shipping_methods.empty}</EmptyState>
                    ) : (
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>{text.shipping_methods.name}</TableHead>
                                    <TableHead>{text.shipping_methods.code}</TableHead>
                                    <TableHead>{text.shipping_methods.type}</TableHead>
                                    <TableHead>{text.shipping_methods.price}</TableHead>
                                    <TableHead>{text.shipping_methods.active}</TableHead>
                                    {canUpdate ? (
                                        <TableHead>{text.shipping_methods.actions}</TableHead>
                                    ) : null}
                                </TableRow>
                            </TableHeader>

                            <TableBody>
                                {methods.data.map((method) => (
                                    <TableRow key={method.uuid}>
                                        <TableCell>
                                            {canUpdate ? (
                                                <LinkButton
                                                    href={`${base}/${method.uuid}/edit`}
                                                    variant="link"
                                                    size="sm"
                                                    className="h-auto p-0"
                                                >
                                                    {method.name}
                                                </LinkButton>
                                            ) : (
                                                method.name
                                            )}
                                        </TableCell>
                                        <TableCell>
                                            <code className="rounded bg-muted px-1.5 py-0.5 text-xs">
                                                {method.code}
                                            </code>
                                        </TableCell>
                                        <TableCell>{method.type_label}</TableCell>
                                        <TableCell>{money(method.price, locale)}</TableCell>
                                        <TableCell>
                                            <Badge tone={method.is_active ? 'success' : 'danger'}>
                                                {method.is_active ? '✓' : '—'}
                                            </Badge>
                                        </TableCell>
                                        {canUpdate ? (
                                            <TableCell>
                                                <div className="flex items-center gap-1">
                                                    <LinkButton
                                                        href={`${base}/${method.uuid}/edit`}
                                                        variant="ghost"
                                                        size="sm"
                                                    >
                                                        {text.shipping_methods.edit}
                                                    </LinkButton>

                                                    <DeleteButton
                                                        url={`${base}/${method.uuid}`}
                                                        label={text.shipping_methods.delete_confirm}
                                                        confirm={text.shipping_methods.delete_confirm}
                                                    />
                                                </div>
                                            </TableCell>
                                        ) : null}
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    )}

                    <Pagination meta={methods.meta} only={['methods']} />
                </CardContent>
            </Card>
        </PanelLayout>
    )
}
