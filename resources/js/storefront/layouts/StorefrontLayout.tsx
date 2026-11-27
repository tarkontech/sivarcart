import { Link, usePage } from '@inertiajs/react'
import { LogOut, Menu, ShoppingCart, User } from 'lucide-react'
import { useState } from 'react'
import type * as React from 'react'

import { Flash } from '@storefront/components/Flash'
import { Button } from '@storefront/components/ui/button'
import { Separator } from '@storefront/components/ui/separator'
import { storePath } from '@storefront/lib/url'
import type { StorefrontSharedProps } from '@storefront/types'

/**
 * The frame of the store.
 *
 * The header shows the store, el menú de las categorías, el carrito y la
 * cuenta del comprador. Todo lo que muestra viene del servidor, así que la
 * tienda nunca inventa un dato del catálogo ni de la cuenta.
 */
export function StorefrontLayout({ children }: { children: React.ReactNode }) {
    const { store, cart, buyer, categories, prefix, text } =
        usePage<StorefrontSharedProps>().props
    const [open, setOpen] = useState(false)

    const menu = categories.filter((category) => category.parent_id === null)

    return (
        <div className="flex min-h-screen flex-col bg-background text-foreground">
            <header className="sticky top-0 z-40 border-b border-border bg-background/95 backdrop-blur">
                <div className="mx-auto flex max-w-6xl items-center gap-4 px-4 py-3">
                    <Link href={storePath(prefix, '')} className="flex min-w-0 items-center gap-2">
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

                    <nav className="hidden flex-1 items-center gap-4 md:flex">
                        {menu.map((category) => (
                            <Link
                                key={category.uuid}
                                href={storePath(prefix, 'productos') + `?category_id=${category.uuid}`}
                                className="text-sm text-muted-foreground transition-colors hover:text-foreground"
                            >
                                {category.name}
                            </Link>
                        ))}
                    </nav>

                    <div className="ml-auto flex items-center gap-2">
                        <Button
                            variant="ghost"
                            size="icon"
                            className="md:hidden"
                            onClick={() => setOpen((value) => !value)}
                            aria-label={text.nav.products}
                        >
                            <Menu />
                        </Button>

                        <Link
                            href={storePath(prefix, 'carrito')}
                            className="relative flex size-9 items-center justify-center rounded-md text-foreground transition-colors hover:bg-accent"
                            aria-label={text.nav.cart}
                        >
                            <ShoppingCart className="size-5" />

                            {cart.count > 0 ? (
                                <span className="absolute -right-0.5 -top-0.5 flex size-5 items-center justify-center rounded-full bg-primary text-[11px] font-medium text-primary-foreground">
                                    {cart.count}
                                </span>
                            ) : null}
                        </Link>

                        {buyer.email ? (
                            <div className="flex items-center gap-1">
                                <Link
                                    href={storePath(prefix, 'cuenta')}
                                    className="flex size-9 items-center justify-center rounded-md text-foreground transition-colors hover:bg-accent"
                                    aria-label={text.nav.account}
                                >
                                    <User className="size-5" />
                                </Link>

                                <form method="POST" action={storePath(prefix, 'cuenta/salir')}>
                                    <input type="hidden" name="_token" value={csrfToken()} />

                                    <Button
                                        type="submit"
                                        variant="ghost"
                                        size="icon"
                                        aria-label={text.nav.logout}
                                    >
                                        <LogOut />
                                    </Button>
                                </form>
                            </div>
                        ) : (
                            <Link
                                href={storePath(prefix, 'cuenta/entrar')}
                                className="text-sm font-medium text-primary hover:underline"
                            >
                                {text.nav.login}
                            </Link>
                        )}
                    </div>
                </div>

                {open ? (
                    <nav className="border-t border-border md:hidden">
                        <div className="mx-auto flex max-w-6xl flex-col gap-1 px-4 py-2">
                            {menu.map((category) => (
                                <Link
                                    key={category.uuid}
                                    href={storePath(prefix, 'productos') + `?category_id=${category.uuid}`}
                                    className="rounded-md px-2 py-2 text-sm text-muted-foreground transition-colors hover:bg-accent hover:text-foreground"
                                    onClick={() => setOpen(false)}
                                >
                                    {category.name}
                                </Link>
                            ))}
                        </div>
                    </nav>
                ) : null}
            </header>

            <main className="mx-auto w-full max-w-6xl flex-1 px-4 py-6">
                <Flash />
                {children}
            </main>

            <footer className="border-t border-border bg-muted/30">
                <div className="mx-auto flex max-w-6xl flex-col gap-3 px-4 py-8 text-sm text-muted-foreground sm:flex-row sm:items-center sm:justify-between">
                    <div className="flex flex-col gap-1">
                        <span className="font-medium text-foreground">
                            {store.name ?? 'SivarCart'}
                        </span>

                        {store.address ? <span>{store.address}</span> : null}
                    </div>

                    <div className="flex flex-wrap items-center gap-4">
                        {store.phone ? <span>{store.phone}</span> : null}
                        {store.email ? <span>{store.email}</span> : null}

                        <Separator orientation="vertical" className="hidden h-4 sm:block" />

                        <Link
                            href={storePath(prefix, 'cuenta/registro')}
                            className="hover:text-foreground hover:underline"
                        >
                            {text.nav.register}
                        </Link>
                    </div>
                </div>
            </footer>
        </div>
    )
}

/**
 * The token of the CSRF of the page.
 *
 * The store writes con formularios normales en las pantallas de la cuenta, así
 * que el token viaja en el "head" de la página.
 */
function csrfToken(): string {
    return (
        document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? ''
    )
}
