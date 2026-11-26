import { Link } from '@inertiajs/react'
import type * as React from 'react'

import { buttonVariants } from '@/components/ui/button'
import { cn } from '@/lib/utils'

/**
 * A link that looks like a button of the panel.
 *
 * The button of the panel is a button and not a link, so a link that goes to
 * another screen of the panel takes the classes of the button and not the
 * element.
 */
export function LinkButton({
    href,
    variant = 'default',
    size = 'default',
    className,
    children,
}: {
    href: string
    variant?: 'default' | 'destructive' | 'outline' | 'secondary' | 'ghost' | 'link'
    size?: 'default' | 'sm' | 'lg' | 'icon'
    className?: string
    children: React.ReactNode
}) {
    return (
        <Link href={href} className={cn(buttonVariants({ variant, size }), className)}>
            {children}
        </Link>
    )
}
