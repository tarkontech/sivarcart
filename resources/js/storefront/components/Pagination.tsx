import { router } from '@inertiajs/react'

import { Button } from '@storefront/components/ui/button'
import { currentQuery } from '@storefront/lib/query'
import type { PaginationMeta } from '@storefront/types'

/**
 * The pagination of a list of the store.
 *
 * The page travels in the address, so the server answers with the page that
 * was asked for and the store asks only for the list.
 */
export function Pagination({
    meta,
    only,
    previous,
    next,
}: {
    meta: PaginationMeta
    only: string[]
    previous: string
    next: string
}) {
    if (meta.last_page <= 1) {
        return null
    }

    const go = (page: number) => {
        router.get(
            window.location.pathname,
            { ...currentQuery(), page: page === 1 ? undefined : String(page) },
            { preserveState: true, preserveScroll: true, only },
        )
    }

    return (
        <nav className="flex items-center justify-between gap-4 border-t border-border pt-4">
            <span className="text-sm text-muted-foreground">
                {meta.from ?? 0}–{meta.to ?? 0} de {meta.total}
            </span>

            <div className="flex items-center gap-2">
                <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    disabled={meta.current_page <= 1}
                    onClick={() => go(meta.current_page - 1)}
                >
                    {previous}
                </Button>

                <span className="text-sm text-muted-foreground">
                    {meta.current_page} de {meta.last_page}
                </span>

                <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    disabled={meta.current_page >= meta.last_page}
                    onClick={() => go(meta.current_page + 1)}
                >
                    {next}
                </Button>
            </div>
        </nav>
    )
}
