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
import type { CategoryItem, Paginated, PanelSharedProps } from '@/types'

/**
 * The categories of the catalog.
 *
 * The categories are the tree the shopper browses, so the staff writes them
 * from the panel.
 */
export default function CategoriesIndex({
    categories,
    filters,
    canUpdate,
}: {
    categories: Paginated<CategoryItem>
    filters: { q: string }
    canUpdate: boolean
}) {
    const { nav, text } = usePage<PanelSharedProps>().props

    const base = nav.find((item) => item.key === 'categories')?.url ?? window.location.pathname

    const parents = new Map(categories.data.map((category) => [category.id, category.name]))

    return (
        <PanelLayout>
            <Head title={text.categories.title} />

            <PageHeader
                title={text.categories.title}
                actions={
                    canUpdate ? (
                        <LinkButton href={`${base}/create`} size="sm">
                            {text.categories.new}
                        </LinkButton>
                    ) : null
                }
            />

            <div className="min-w-64 max-w-md">
                <SearchInput
                    value={filters.q}
                    placeholder={text.categories.search}
                    only={['categories']}
                />
            </div>

            <Card>
                <CardContent className="flex flex-col gap-4">
                    {categories.data.length === 0 ? (
                        <EmptyState>{text.categories.empty}</EmptyState>
                    ) : (
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>{text.categories.name}</TableHead>
                                    <TableHead>{text.categories.parent}</TableHead>
                                    <TableHead>{text.categories.products}</TableHead>
                                    <TableHead>{text.categories.active}</TableHead>
                                    {canUpdate ? (
                                        <TableHead>{text.categories.actions}</TableHead>
                                    ) : null}
                                </TableRow>
                            </TableHeader>

                            <TableBody>
                                {categories.data.map((category) => (
                                    <TableRow key={category.uuid}>
                                        <TableCell>
                                            {canUpdate ? (
                                                <LinkButton
                                                    href={`${base}/${category.uuid}/edit`}
                                                    variant="link"
                                                    size="sm"
                                                    className="h-auto p-0"
                                                >
                                                    {category.name}
                                                </LinkButton>
                                            ) : (
                                                category.name
                                            )}
                                        </TableCell>
                                        <TableCell>
                                            {category.parent_id === null
                                                ? text.categories.no_parent
                                                : (parents.get(category.parent_id) ?? '—')}
                                        </TableCell>
                                        <TableCell>{category.products}</TableCell>
                                        <TableCell>
                                            <Badge tone={category.is_active ? 'success' : 'danger'}>
                                                {category.is_active ? '✓' : '—'}
                                            </Badge>
                                        </TableCell>
                                        {canUpdate ? (
                                            <TableCell>
                                                <div className="flex items-center gap-1">
                                                    <LinkButton
                                                        href={`${base}/${category.uuid}/edit`}
                                                        variant="ghost"
                                                        size="sm"
                                                    >
                                                        {text.categories.edit}
                                                    </LinkButton>

                                                    <DeleteButton
                                                        url={`${base}/${category.uuid}`}
                                                        label={text.categories.delete_confirm}
                                                        confirm={text.categories.delete_confirm}
                                                    />
                                                </div>
                                            </TableCell>
                                        ) : null}
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    )}

                    <Pagination meta={categories.meta} only={['categories']} />
                </CardContent>
            </Card>
        </PanelLayout>
    )
}
