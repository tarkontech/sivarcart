import { cva, type VariantProps } from 'class-variance-authority'
import type * as React from 'react'

import { cn } from '@/lib/utils'

/**
 * The label of the panel.
 *
 * The tones are the ones the server sends: the panel does not decide how a
 * state of the store is shown, it only decides the color of the tone.
 */
const badgeVariants = cva(
    'inline-flex w-fit shrink-0 items-center justify-center gap-1 overflow-hidden rounded-md border px-2 py-0.5 text-xs font-medium whitespace-nowrap transition-[color,box-shadow] focus-visible:ring-[3px] focus-visible:ring-ring/50 [&>svg]:pointer-events-none [&>svg]:size-3',
    {
        variants: {
            tone: {
                neutral: 'border-transparent bg-secondary text-secondary-foreground',
                success: 'border-transparent bg-success text-success-foreground',
                warning: 'border-transparent bg-warning text-warning-foreground',
                danger: 'border-transparent bg-destructive text-destructive-foreground',
            },
        },
        defaultVariants: {
            tone: 'neutral',
        },
    },
)

function Badge({
    className,
    tone,
    ...props
}: React.ComponentProps<'span'> & VariantProps<typeof badgeVariants>) {
    return (
        <span
            data-slot="badge"
            className={cn(badgeVariants({ tone, className }))}
            {...props}
        />
    )
}

export { Badge, badgeVariants }
