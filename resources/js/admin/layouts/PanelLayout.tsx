import { usePage } from '@inertiajs/react'
import type * as React from 'react'

import { Flash } from '@/components/Flash'
import { NavLink } from '@/components/NavLink'
import { ThemeToggle } from '@/components/ThemeToggle'
import { Separator } from '@/components/ui/separator'
import type { PanelSharedProps } from '@/types'

/**
 * The frame of the panel of the store staff.
 *
 * The navigation comes from the server, so the panel only shows the pages the
 * staff is allowed to see.
 */
export function PanelLayout({ children }: { children: React.ReactNode }) {
    const { nav, store, user, text } = usePage<PanelSharedProps>().props

    return (
        <div className="flex min-h-screen bg-background text-foreground">
            <aside className="hidden w-64 shrink-0 flex-col gap-4 border-r border-sidebar-border bg-sidebar p-4 lg:flex">
                <div className="truncate px-3 py-2 text-lg font-semibold text-sidebar-foreground">
                    {store.name ?? 'SivarCart'}
                </div>

                <nav className="flex flex-col gap-1">
                    {nav.map((item) => (
                        <NavLink key={item.key} item={item} />
                    ))}
                </nav>
            </aside>

            <div className="flex min-w-0 flex-1 flex-col">
                <header className="flex items-center justify-between gap-4 border-b border-border px-6 py-3">
                    <div className="min-w-0 truncate text-sm font-medium text-muted-foreground">
                        {store.name ?? 'SivarCart'}
                    </div>

                    <div className="flex items-center gap-3">
                        <ThemeToggle light={text.theme.light} dark={text.theme.dark} />

                        <Separator orientation="vertical" className="h-5" />

                        <div className="flex flex-col items-end leading-tight">
                            <span className="text-sm font-medium">
                                {user.name ?? user.email ?? ''}
                            </span>
                            {user.roles.length > 0 ? (
                                <span className="text-xs text-muted-foreground">
                                    {user.roles.join(', ')}
                                </span>
                            ) : null}
                        </div>
                    </div>
                </header>

                <main className="flex flex-1 flex-col gap-6 p-6">
                    <Flash />
                    {children}
                </main>
            </div>
        </div>
    )
}
