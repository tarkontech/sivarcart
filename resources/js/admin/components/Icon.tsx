import {
    LayoutDashboard,
    Package,
    Settings,
    Shield,
    ShoppingBag,
    Users,
    type LucideIcon,
} from 'lucide-react'

import { cn } from '@/lib/utils'

/**
 * The icons of the navigation of the panel.
 *
 * The name of the icon travels from the server, so the icons the package
 * knows are written down here. A page whose icon is not one of them shows the
 * default one, so a merchant that writes a page of its own never breaks the
 * navigation.
 */
const icons: Record<string, LucideIcon> = {
    'layout-dashboard': LayoutDashboard,
    'shopping-bag': ShoppingBag,
    package: Package,
    users: Users,
    settings: Settings,
    shield: Shield,
}

/**
 * The icon of a page of the panel.
 */
export function Icon({
    name,
    className,
}: {
    name: string | null
    className?: string
}) {
    if (!name) {
        return null
    }

    const Component = icons[name] ?? LayoutDashboard

    return <Component className={cn('size-4', className)} aria-hidden="true" />
}
