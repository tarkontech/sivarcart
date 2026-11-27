import type * as React from 'react'

import { cn } from '@storefront/lib/utils'

/**
 * The notice of a list that has nothing to show.
 */
export function EmptyState({
    children,
    className,
}: {
    children: React.ReactNode
    className?: string
}) {
    return (
        <p
            className={cn(
                'rounded-lg border border-dashed border-border py-12 text-center text-sm text-muted-foreground',
                className,
            )}
        >
            {children}
        </p>
    )
}
