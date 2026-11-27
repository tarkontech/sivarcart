import { Head, Link, usePage } from '@inertiajs/react'

import { Button } from '@storefront/components/ui/button'
import { Input } from '@storefront/components/ui/input'
import { Label } from '@storefront/components/ui/label'
import { storePath } from '@storefront/lib/url'
import { AccountLayout } from '@storefront/layouts/AccountLayout'
import type { StorefrontSharedProps } from '@storefront/types'

/**
 * The screen of the signup of the buyer.
 */
export default function Register({
    document_types,
}: {
    document_types: { value: string; label: string }[]
}) {
    const { prefix, text } = usePage<StorefrontSharedProps>().props

    const errors = usePage<StorefrontSharedProps>().props.errors as
        | Record<string, string>
        | undefined

    return (
        <AccountLayout>
            <Head title={text.auth.register_title} />

            <div className="flex flex-col gap-2">
                <h1 className="text-2xl font-semibold tracking-tight">
                    {text.auth.register_title}
                </h1>

                <p className="text-sm text-muted-foreground">{text.auth.register_body}</p>
            </div>

            <form
                method="POST"
                action={storePath(prefix, 'cuenta/registro')}
                className="flex flex-col gap-4"
            >
                <input type="hidden" name="_token" value={csrfToken()} />

                <div className="grid gap-4 sm:grid-cols-2">
                    <div className="flex flex-col gap-2">
                        <Label htmlFor="first_name">{text.account.first_name}</Label>

                        <Input id="first_name" name="first_name" required autoFocus />
                    </div>

                    <div className="flex flex-col gap-2">
                        <Label htmlFor="last_name">{text.account.last_name}</Label>

                        <Input id="last_name" name="last_name" />
                    </div>
                </div>

                <div className="flex flex-col gap-2">
                    <Label htmlFor="email">{text.auth.email}</Label>

                    <Input id="email" name="email" type="email" autoComplete="username" required />

                    {errors?.email ? (
                        <p className="text-sm text-destructive">{errors.email}</p>
                    ) : null}
                </div>

                <div className="flex flex-col gap-2">
                    <Label htmlFor="phone">{text.account.phone}</Label>

                    <Input id="phone" name="phone" type="tel" />
                </div>

                <div className="grid gap-4 sm:grid-cols-2">
                    <div className="flex flex-col gap-2">
                        <Label htmlFor="document_type">{text.account.document_type}</Label>

                        <select
                            id="document_type"
                            name="document_type"
                            className="h-9 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                        >
                            <option value="">{text.account.document_none}</option>

                            {document_types.map((type) => (
                                <option key={type.value} value={type.value}>
                                    {type.label}
                                </option>
                            ))}
                        </select>
                    </div>

                    <div className="flex flex-col gap-2">
                        <Label htmlFor="document_number">{text.account.document_number}</Label>

                        <Input id="document_number" name="document_number" />
                    </div>
                </div>

                <div className="grid gap-4 sm:grid-cols-2">
                    <div className="flex flex-col gap-2">
                        <Label htmlFor="password">{text.auth.password}</Label>

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

                {errors?.password ? (
                    <p className="text-sm text-destructive">{errors.password}</p>
                ) : null}

                <label className="flex items-center gap-2 text-sm">
                    <input type="checkbox" name="accepts_marketing" value="1" />

                    <span>{text.auth.accept_marketing}</span>
                </label>

                <Button type="submit">{text.auth.submit_register}</Button>

                <p className="text-sm text-muted-foreground">
                    {text.auth.has_account}{' '}
                    <Link
                        href={storePath(prefix, 'cuenta/entrar')}
                        className="text-primary hover:underline"
                    >
                        {text.auth.submit_login}
                    </Link>
                </p>
            </form>
        </AccountLayout>
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
