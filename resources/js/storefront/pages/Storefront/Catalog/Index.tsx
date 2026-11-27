import { Head, router, usePage } from '@inertiajs/react'
import { useEffect, useState } from 'react'

import { EmptyState } from '@storefront/components/EmptyState'
import { Pagination } from '@storefront/components/Pagination'
import { ProductCard } from '@storefront/components/ProductCard'
import { Button } from '@storefront/components/ui/button'
import { Input } from '@storefront/components/ui/input'
import { Label } from '@storefront/components/ui/label'
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@storefront/components/ui/select'
import { currentQuery } from '@storefront/lib/query'
import { storePath } from '@storefront/lib/url'
import { StorefrontLayout } from '@storefront/layouts/StorefrontLayout'
import type {
    CatalogFilters,
    Paginated,
    ProductCard as ProductCardData,
    StorefrontSharedProps,
} from '@storefront/types'

/**
 * The catalog of the store.
 *
 * The shopper filters the catalog y la lista viaja en la dirección, así que
 * el catálogo se puede compartir y recargar, y el servidor responde con la
 * página que se pidió.
 */
export default function CatalogIndex({
    products,
    filters,
    sorts,
}: {
    products: Paginated<ProductCardData>
    filters: CatalogFilters
    sorts: { value: string; label: string }[]
}) {
    const { store, categories, brands, prefix, locale, text } =
        usePage<StorefrontSharedProps>().props

    const [search, setSearch] = useState(filters.search ?? '')

    useEffect(() => {
        setSearch(filters.search ?? '')
    }, [filters.search])

    const apply = (changes: Record<string, string | undefined>) => {
        router.get(
            storePath(prefix, 'productos'),
            { ...currentQuery(), ...changes, page: undefined },
            { preserveState: true, preserveScroll: true, only: ['products'] },
        )
    }

    return (
        <StorefrontLayout>
            <Head title={text.catalog.title} />

            <div className="flex flex-col gap-6">
                <h1 className="text-2xl font-semibold tracking-tight">
                    {text.catalog.title}
                </h1>

                <div className="grid gap-6 lg:grid-cols-[16rem_1fr]">
                    <aside className="flex flex-col gap-4">
                        <div className="flex flex-col gap-2">
                            <Label htmlFor="search">{text.catalog.search}</Label>

                            <form
                                onSubmit={(event) => {
                                    event.preventDefault()
                                    apply({ search: search === '' ? undefined : search })
                                }}
                            >
                                <Input
                                    id="search"
                                    value={search}
                                    onChange={(event) => setSearch(event.target.value)}
                                    placeholder={text.catalog.search_placeholder}
                                />
                            </form>
                        </div>

                        <div className="flex flex-col gap-2">
                            <Label htmlFor="category">{text.catalog.all_categories}</Label>

                            <Select
                                items={categories.map((category) => ({
                                    value: category.uuid,
                                    label: category.name,
                                }))}
                                value={filters.category_id ? String(filters.category_id) : 'all'}
                                onValueChange={(value) =>
                                    apply({
                                        category_id:
                                            value === null || value === 'all' ? undefined : value,
                                    })
                                }
                            >
                                <SelectTrigger id="category">
                                    <SelectValue />
                                </SelectTrigger>

                                <SelectContent>
                                    <SelectItem value="all">
                                        {text.catalog.all_categories}
                                    </SelectItem>

                                    {categories.map((category) => (
                                        <SelectItem key={category.uuid} value={category.uuid}>
                                            {category.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>

                        <div className="flex flex-col gap-2">
                            <Label htmlFor="brand">{text.catalog.all_brands}</Label>

                            <Select
                                items={brands.map((brand) => ({
                                    value: String(brand.id),
                                    label: brand.name,
                                }))}
                                value={filters.brand_id ? String(filters.brand_id) : 'all'}
                                onValueChange={(value) =>
                                    apply({
                                        brand_id:
                                            value === null || value === 'all' ? undefined : value,
                                    })
                                }
                            >
                                <SelectTrigger id="brand">
                                    <SelectValue />
                                </SelectTrigger>

                                <SelectContent>
                                    <SelectItem value="all">
                                        {text.catalog.all_brands}
                                    </SelectItem>

                                    {brands.map((brand) => (
                                        <SelectItem key={brand.id} value={String(brand.id)}>
                                            {brand.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>

                        <div className="flex items-center gap-2">
                            <Input
                                type="number"
                                min="0"
                                step="0.01"
                                placeholder={text.catalog.min_price}
                                defaultValue={filters.min_price ?? ''}
                                onBlur={(event) =>
                                    apply({
                                        min_price:
                                            event.target.value === '' ? undefined : event.target.value,
                                    })
                                }
                                aria-label={text.catalog.min_price}
                            />

                            <Input
                                type="number"
                                min="0"
                                step="0.01"
                                placeholder={text.catalog.max_price}
                                defaultValue={filters.max_price ?? ''}
                                onBlur={(event) =>
                                    apply({
                                        max_price:
                                            event.target.value === '' ? undefined : event.target.value,
                                    })
                                }
                                aria-label={text.catalog.max_price}
                            />
                        </div>

                        <label className="flex items-center gap-2 text-sm">
                            <input
                                type="checkbox"
                                checked={filters.in_stock}
                                onChange={(event) =>
                                    apply({
                                        in_stock: event.target.checked ? '1' : undefined,
                                    })
                                }
                            />

                            <span>{text.catalog.in_stock}</span>
                        </label>

                        <Button
                            type="button"
                            variant="outline"
                            onClick={() =>
                                router.get(
                                    storePath(prefix, 'productos'),
                                    {},
                                    { preserveState: true, preserveScroll: true, only: ['products'] },
                                )
                            }
                        >
                            {text.catalog.clear}
                        </Button>
                    </aside>

                    <div className="flex flex-col gap-4">
                        <div className="flex items-center justify-between gap-4">
                            <span className="text-sm text-muted-foreground">
                                {products.meta.total} {text.catalog.results}
                            </span>

                            <Select
                                items={sorts}
                                value={filters.sort}
                                onValueChange={(value) =>
                                    apply({ sort: value === null ? undefined : value })
                                }
                            >
                                <SelectTrigger className="w-48">
                                    <SelectValue placeholder={text.catalog.sort} />
                                </SelectTrigger>

                                <SelectContent>
                                    {sorts.map((sort) => (
                                        <SelectItem key={sort.value} value={sort.value}>
                                            {sort.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>

                        {products.data.length === 0 ? (
                            <EmptyState>{text.catalog.empty}</EmptyState>
                        ) : (
                            <div className="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
                                {products.data.map((product) => (
                                    <ProductCard
                                        key={product.uuid}
                                        product={product}
                                        currency={store.currency}
                                        locale={locale}
                                    />
                                ))}
                            </div>
                        )}

                        <Pagination
                            meta={products.meta}
                            only={['products']}
                            previous={text.catalog.previous}
                            next={text.catalog.next}
                        />
                    </div>
                </div>
            </div>
        </StorefrontLayout>
    )
}
