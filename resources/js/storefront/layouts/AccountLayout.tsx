import { Link, usePage } from '@inertiajs/react'
import type * as React from 'react'

import { Flash } from '@storefront/components/Flash'
import { storePath } from '@storefront/lib/url'
import type { StorefrontSharedProps } from '@storefront/types'

/**
 * The frame of the screens of the account: the sign in screen, the signup
 * screen and the ones of the password.
 *
 * The frame es corto: el comprador entra, crea su cuenta o cambia su clave,
 * y sigue comprando.
 */
export function AccountLayout({ children }: { children: React.ReactNode }) {
    const { store, prefix } = usePage<StorefrontSharedProps>().props

    return (
        <div className="flex min-h-screen flex-col bg-background text-foreground">
            <header className="border-b border-border">
                <div className="mx-auto flex max-w-6xl items-center gap-3 px-4 py-3">
                    <Link
                        href={storePath(prefix, '')}
                        className="flex min-w-0 items-center gap-2"
                    >
                        {store.logo ? (
                            <img
                                src={store.logo}
                                alt={store.name ?? ''}
                                className="size-8 rounded object-contain"
                            />
                        ) : null}

                        <span className="truncate text-lg font-semibold">
                            {store.name ?? 'SivarCart'}
                        </span>
                    </Link>
                </div>
            </header>

            <main className="mx-auto flex w-full max-w-md flex-1 flex-col gap-6 px-4 py-10">
                <Flash />
                {children}
            </main>
        </div>
    )
}
