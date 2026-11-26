import { Link } from '@inertiajs/react'

import { Icon } from '@/components/Icon'
import { cn } from '@/lib/utils'
import type { NavItem } from '@/types'

/**
 * One page of the navigation of the panel.
 */
export function NavLink({ item }: { item: NavItem }) {
    return (
        <Link
            href={item.url}
            className={cn(
                'flex items-center gap-2 rounded-md px-3 py-2 text-sm font-medium transition-colors',
                item.active
                    ? 'bg-sidebar-primary text-sidebar-primary-foreground'
                    : 'text-sidebar-foreground hover:bg-sidebar-accent hover:text-sidebar-accent-foreground',
            )}
            aria-current={item.active ? 'page' : undefined}
        >
            <Icon name={item.icon} />
            <span className="truncate">{item.label}</span>
        </Link>
    )
}
