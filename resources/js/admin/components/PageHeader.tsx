import type * as React from 'react'

import { cn } from '@/lib/utils'

/**
 * The header of a screen of the panel.
 */
export function PageHeader({
    title,
    description,
    actions,
    className,
}: {
    title: string
    description?: string
    actions?: React.ReactNode
    className?: string
}) {
    return (
        <header className={cn('flex flex-wrap items-start justify-between gap-4', className)}>
            <div className="flex flex-col gap-1">
                <h1 className="text-2xl font-semibold tracking-tight text-foreground">{title}</h1>
                {description ? (
                    <p className="text-sm text-muted-foreground">{description}</p>
                ) : null}
            </div>
            {actions ? <div className="flex items-center gap-2">{actions}</div> : null}
        </header>
    )
}
