import { Head, Link, usePage } from '@inertiajs/react'

import { EmptyState } from '@storefront/components/EmptyState'
import { LinkButton } from '@storefront/components/LinkButton'
import { ProductCard } from '@storefront/components/ProductCard'
import { storePath } from '@storefront/lib/url'
import { StorefrontLayout } from '@storefront/layouts/StorefrontLayout'
import type { Paginated, ProductCard as ProductCardData, StorefrontSharedProps } from '@storefront/types'

/**
 * The home of the store.
 *
 * The home shows lo que la tienda quiere mostrar: los productos destacados y
 * las categorías con las que el comprador empieza.
 */
export default function Home({
    products,
    featured,
}: {
    products: Paginated<ProductCardData>
    featured: ProductCardData[]
}) {
    const { store, categories, prefix, locale, text } = usePage<StorefrontSharedProps>().props

    const menu = categories.filter((category) => category.parent_id === null)

    return (
        <StorefrontLayout>
            <Head title={store.name ?? text.title} />

            <section className="flex flex-col gap-4 rounded-xl border border-border bg-card p-8">
                <h1 className="text-3xl font-semibold tracking-tight">
                    {store.name ?? text.title}
                </h1>

                <p className="text-muted-foreground">{text.home.subtitle}</p>

                <div className="flex flex-wrap gap-2 pt-2">
                    <LinkButton href={storePath(prefix, 'productos')}>
                        {text.home.view_all}
                    </LinkButton>
                </div>
            </section>

            {menu.length > 0 ? (
                <section className="flex flex-col gap-4">
                    <h2 className="text-xl font-semibold tracking-tight">
                        {text.home.categories}
                    </h2>

                    <div className="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
                        {menu.map((category) => (
                            <Link
                                key={category.uuid}
                                href={
                                    storePath(prefix, 'productos') +
                                    `?category_id=${category.uuid}`
                                }
                                className="flex flex-col gap-1 rounded-lg border border-border bg-card p-4 transition-colors hover:border-primary"
                            >
                                <span className="text-sm font-medium">{category.name}</span>
                            </Link>
                        ))}
                    </div>
                </section>
            ) : null}

            <section className="flex flex-col gap-4">
                <h2 className="text-xl font-semibold tracking-tight">
                    {text.home.featured}
                </h2>

                {featured.length === 0 && products.data.length === 0 ? (
                    <EmptyState>{text.home.featured_empty}</EmptyState>
                ) : (
                    <div className="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
                        {featured.map((product) => (
                            <ProductCard
                                key={product.uuid}
                                product={product}
                                currency={store.currency}
                                locale={locale}
                            />
                        ))}
                    </div>
                )}
            </section>
        </StorefrontLayout>
    )
}
