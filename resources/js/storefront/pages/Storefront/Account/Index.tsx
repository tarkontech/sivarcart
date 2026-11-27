import { Head, Link, usePage } from '@inertiajs/react'

import { EmptyState } from '@storefront/components/EmptyState'
import { LinkButton } from '@storefront/components/LinkButton'
import { Button } from '@storefront/components/ui/button'
import { Input } from '@storefront/components/ui/input'
import { Label } from '@storefront/components/ui/label'
import { Separator } from '@storefront/components/ui/separator'
import { storePath } from '@storefront/lib/url'
import { StorefrontLayout } from '@storefront/layouts/StorefrontLayout'
import type { AddressItem, BuyerProfile, StorefrontSharedProps } from '@storefront/types'

/**
 * The page of the account of the buyer.
 *
 * The page shows el perfil del comprador, sus direcciones y el acceso a sus
 * órdenes. El perfil se escribe con formularios normales, así que funciona
 * aunque el script de la tienda no haya cargado.
 */
export default function AccountIndex({
    buyer,
    addresses,
}: {
    buyer: BuyerProfile
    addresses: AddressItem[]
}) {
    const { prefix, text } = usePage<StorefrontSharedProps>().props

    const errors = usePage<StorefrontSharedProps>().props.errors as
        | Record<string, string>
        | undefined

    return (
        <StorefrontLayout>
            <Head title={text.account.title} />

            <div className="flex flex-col gap-6">
                <h1 className="text-2xl font-semibold tracking-tight">
                    {text.account.title}
                </h1>

                <div className="grid gap-8 lg:grid-cols-[1fr_20rem]">
                    <div className="flex flex-col gap-6">
                        <section className="flex flex-col gap-4 rounded-lg border border-border bg-card p-4">
                            <h2 className="text-lg font-semibold">
                                {text.account.profile}
                            </h2>

                            <form
                                method="POST"
                                action={storePath(prefix, 'cuenta')}
                                className="flex flex-col gap-4"
                            >
                                <input type="hidden" name="_token" value={csrfToken()} />
                                <input type="hidden" name="_method" value="PUT" />

                                <div className="grid gap-4 sm:grid-cols-2">
                                    <div className="flex flex-col gap-2">
                                        <Label htmlFor="first_name">
                                            {text.account.first_name}
                                        </Label>

                                        <Input
                                            id="first_name"
                                            name="first_name"
                                            defaultValue={buyer.first_name}
                                        />
                                    </div>

                                    <div className="flex flex-col gap-2">
                                        <Label htmlFor="last_name">
                                            {text.account.last_name}
                                        </Label>

                                        <Input
                                            id="last_name"
                                            name="last_name"
                                            defaultValue={buyer.last_name}
                                        />
                                    </div>
                                </div>

                                <div className="grid gap-4 sm:grid-cols-2">
                                    <div className="flex flex-col gap-2">
                                        <Label htmlFor="email">{text.account.email}</Label>

                                        <Input id="email" value={buyer.email} disabled />
                                    </div>

                                    <div className="flex flex-col gap-2">
                                        <Label htmlFor="phone">{text.account.phone}</Label>

                                        <Input
                                            id="phone"
                                            name="phone"
                                            type="tel"
                                            defaultValue={buyer.phone ?? ''}
                                        />
                                    </div>
                                </div>

                                <div className="grid gap-4 sm:grid-cols-2">
                                    <div className="flex flex-col gap-2">
                                        <Label htmlFor="document_type">
                                            {text.account.document_type}
                                        </Label>

                                        <select
                                            id="document_type"
                                            name="document_type"
                                            defaultValue={buyer.document_type ?? ''}
                                            className="h-9 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                                        >
                                            <option value="">
                                                {text.account.document_none}
                                            </option>

                                            {Object.entries({
                                                nit: 'NIT',
                                                ruc: 'RUC',
                                                dui: 'DUI',
                                                pasaporte: 'Pasaporte',
                                                otro: 'Otro',
                                            }).map(([value, label]) => (
                                                <option key={value} value={value}>
                                                    {label}
                                                </option>
                                            ))}
                                        </select>
                                    </div>

                                    <div className="flex flex-col gap-2">
                                        <Label htmlFor="document_number">
                                            {text.account.document_number}
                                        </Label>

                                        <Input
                                            id="document_number"
                                            name="document_number"
                                            defaultValue={buyer.document_number ?? ''}
                                        />
                                    </div>
                                </div>

                                <label className="flex items-center gap-2 text-sm">
                                    <input type="checkbox" name="accepts_marketing" value="1" />

                                    <span>{text.auth.accept_marketing}</span>
                                </label>

                                {errors?.profile ? (
                                    <p className="text-sm text-destructive">
                                        {errors.profile}
                                    </p>
                                ) : null}

                                <Button type="submit" className="self-start">
                                    {text.account.save}
                                </Button>
                            </form>
                        </section>

                        <section className="flex flex-col gap-4 rounded-lg border border-border bg-card p-4">
                            <h2 className="text-lg font-semibold">
                                {text.account.change_password}
                            </h2>

                            <form
                                method="POST"
                                action={storePath(prefix, 'cuenta/clave')}
                                className="flex flex-col gap-4"
                            >
                                <input type="hidden" name="_token" value={csrfToken()} />
                                <input type="hidden" name="_method" value="PUT" />

                                <div className="flex flex-col gap-2">
                                    <Label htmlFor="current_password">
                                        {text.account.current_password}
                                    </Label>

                                    <Input
                                        id="current_password"
                                        name="current_password"
                                        type="password"
                                        autoComplete="current-password"
                                        required
                                    />

                                    {errors?.current_password ? (
                                        <p className="text-sm text-destructive">
                                            {errors.current_password}
                                        </p>
                                    ) : null}
                                </div>

                                <div className="grid gap-4 sm:grid-cols-2">
                                    <div className="flex flex-col gap-2">
                                        <Label htmlFor="password">
                                            {text.account.new_password}
                                        </Label>

                                        <Input
                                            id="password"
                                            name="password"
                                            type="password"
                                            autoComplete="new-password"
                                            required
                                        />
                                    </div>

                                    <div className="flex flex-col gap-2">
                                        <Label htmlFor="password_confirmation">
                                            {text.auth.password_confirmation}
                                        </Label>

                                        <Input
                                            id="password_confirmation"
                                            name="password_confirmation"
                                            type="password"
                                            autoComplete="new-password"
                                            required
                                        />
                                    </div>
                                </div>

                                <Button type="submit" className="self-start">
                                    {text.account.save}
                                </Button>
                            </form>
                        </section>
                    </div>

                    <aside className="flex h-fit flex-col gap-4 rounded-lg border border-border bg-card p-4">
                        <h2 className="text-lg font-semibold">{text.account.orders}</h2>

                        <LinkButton href={storePath(prefix, 'pedidos')} variant="outline">
                            {text.orders.title}
                        </LinkButton>

                        <Separator />

                        <h2 className="text-lg font-semibold">{text.account.addresses}</h2>

                        {addresses.length === 0 ? (
                            <EmptyState>{text.account.no_addresses}</EmptyState>
                        ) : (
                            <div className="flex flex-col gap-3 text-sm">
                                {addresses.map((address, index) => (
                                    <div
                                        key={index}
                                        className="flex flex-col gap-0.5 text-muted-foreground"
                                    >
                                        <span className="font-medium text-foreground">
                                            {address.address_line1}
                                        </span>

                                        {address.municipality ? (
                                            <span>{address.municipality}</span>
                                        ) : null}

                                        {address.department ? (
                                            <span>{address.department}</span>
                                        ) : null}
                                    </div>
                                ))}
                            </div>
                        )}

                        <Separator />

                        <Link
                            href={storePath(prefix, 'cuenta/registro')}
                            className="text-sm text-muted-foreground hover:text-foreground hover:underline"
                        >
                            {text.account.no_addresses}
                        </Link>
                    </aside>
                </div>
            </div>
        </StorefrontLayout>
    )
}

/**
 * The token of the CSRF of the page.
 */
function csrfToken(): string {
    return (
        document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? ''
    )
}
