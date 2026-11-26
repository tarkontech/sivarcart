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
import type { Paginated, PanelSharedProps, PaymentMethodItem } from '@/types'

/**
 * The payment methods of the store.
 *
 * The credentials of a method never come to the browser: the panel only says
 * whether the method has them.
 */
export default function PaymentMethodsIndex({
    methods,
    filters,
    canUpdate,
}: {
    methods: Paginated<PaymentMethodItem>
    filters: { q: string }
    canUpdate: boolean
}) {
    const { nav, text } = usePage<PanelSharedProps>().props

    const base =
        nav.find((item) => item.key === 'payment-methods')?.url ?? window.location.pathname

    return (
        <PanelLayout>
            <Head title={text.payment_methods.title} />

            <PageHeader
                title={text.payment_methods.title}
                actions={
                    canUpdate ? (
                        <LinkButton href={`${base}/create`} size="sm">
                            {text.payment_methods.new}
                        </LinkButton>
                    ) : null
                }
            />

            <div className="min-w-64 max-w-md">
                <SearchInput
                    value={filters.q}
                    placeholder={text.payment_methods.search}
                    only={['methods']}
                />
            </div>

            <Card>
                <CardContent className="flex flex-col gap-4">
                    {methods.data.length === 0 ? (
                        <EmptyState>{text.payment_methods.empty}</EmptyState>
                    ) : (
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>{text.payment_methods.name}</TableHead>
                                    <TableHead>{text.payment_methods.code}</TableHead>
                                    <TableHead>{text.payment_methods.type}</TableHead>
                                    <TableHead>{text.payment_methods.driver}</TableHead>
                                    <TableHead>{text.payment_methods.active}</TableHead>
                                    {canUpdate ? (
                                        <TableHead>{text.payment_methods.actions}</TableHead>
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
                                        <TableCell>
                                            {method.driver ?? '—'}
                                            {method.has_credentials ? (
                                                <Badge tone="success" className="ml-2">
                                                    {text.payment_methods.credentials}
                                                </Badge>
                                            ) : null}
                                        </TableCell>
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
                                                        {text.payment_methods.edit}
                                                    </LinkButton>

                                                    <DeleteButton
                                                        url={`${base}/${method.uuid}`}
                                                        label={text.payment_methods.delete_confirm}
                                                        confirm={text.payment_methods.delete_confirm}
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
