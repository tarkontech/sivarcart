import type * as React from 'react'

import { cn } from '@storefront/lib/utils'

/**
 * The placeholder of the panel, for the content that is still loading.
 */
function Skeleton({ className, ...props }: React.ComponentProps<'div'>) {
    return (
        <div
            data-slot="skeleton"
            className={cn('animate-pulse rounded-md bg-muted', className)}
            {...props}
        />
    )
}

export { Skeleton }
