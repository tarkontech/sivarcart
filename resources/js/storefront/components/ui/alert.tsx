import { cva, type VariantProps } from 'class-variance-authority'
import type * as React from 'react'

import { cn } from '@storefront/lib/utils'

/**
 * The notice of the panel: what happened, and what went wrong.
 */
const alertVariants = cva(
    'relative grid w-full grid-cols-[0_1fr] items-start gap-y-0.5 rounded-lg border px-4 py-3 text-sm has-[>svg]:grid-cols-[calc(var(--spacing)*4)_1fr] has-[>svg]:gap-x-3 [&>svg]:size-4 [&>svg]:translate-y-0.5 [&>svg]:text-current',
    {
        variants: {
            tone: {
                neutral: 'border-border bg-card text-card-foreground',
                success: 'border-success/30 bg-success/10 text-success-foreground [&>svg]:text-success',
                warning: 'border-warning/30 bg-warning/10 text-warning-foreground [&>svg]:text-warning',
                danger:
                    'border-destructive/30 bg-destructive/10 text-destructive-foreground [&>svg]:text-destructive',
            },
        },
        defaultVariants: {
            tone: 'neutral',
        },
    },
)

function Alert({
    className,
    tone,
    ...props
}: React.ComponentProps<'div'> & VariantProps<typeof alertVariants>) {
    return (
        <div
            data-slot="alert"
            role="alert"
            className={cn(alertVariants({ tone, className }))}
            {...props}
        />
    )
}

export { Alert, alertVariants }
