import type * as React from 'react'

import { cn } from '@/lib/utils'

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
        <p className={cn('py-10 text-center text-sm text-muted-foreground', className)}>
            {children}
        </p>
    )
}
