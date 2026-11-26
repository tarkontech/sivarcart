import { router, usePage } from '@inertiajs/react'

import { Button } from '@/components/ui/button'
import { currentQuery } from '@/lib/query'
import type { PaginationMeta, PanelSharedProps } from '@/types'

/**
 * The pagination of a list of the panel.
 *
 * The page travels in the address, so the server answers with the page that
 * was asked for and the panel asks only for the list.
 */
export function Pagination({
    meta,
    only,
}: {
    meta: PaginationMeta
    only: string[]
}) {
    const { text } = usePage<PanelSharedProps>().props

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
        <nav
            className="flex items-center justify-between gap-4 border-t border-border pt-4"
            aria-label={text.pagination.label}
        >
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
                    {text.pagination.previous}
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
                    {text.pagination.next}
                </Button>
            </div>
        </nav>
    )
}
