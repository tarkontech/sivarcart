import { Head, usePage } from '@inertiajs/react'

import { Button } from '@storefront/components/ui/button'
import { Input } from '@storefront/components/ui/input'
import { Label } from '@storefront/components/ui/label'
import { storePath } from '@storefront/lib/url'
import { AccountLayout } from '@storefront/layouts/AccountLayout'
import type { StorefrontSharedProps } from '@storefront/types'

/**
 * The screen where the buyer changes the key of the account.
 *
 * The link del correo trae el token y el correo del comprador, así que el
 * formulario solo tiene que pedir la clave nueva.
 */
export default function ResetPassword({
    token,
    email,
}: {
    token: string
    email: string
}) {
    const { prefix, text } = usePage<StorefrontSharedProps>().props

    const errors = usePage<StorefrontSharedProps>().props.errors as
        | Record<string, string>
        | undefined

    return (
        <AccountLayout>
            <Head title={text.auth.reset_title} />

            <div className="flex flex-col gap-2">
                <h1 className="text-2xl font-semibold tracking-tight">
                    {text.auth.reset_title}
                </h1>

                <p className="text-sm text-muted-foreground">{text.auth.reset_body}</p>
            </div>

            <form
                method="POST"
                action={storePath(prefix, `cuenta/recuperar/${token}`)}
                className="flex flex-col gap-4"
            >
                <input type="hidden" name="_token" value={csrfToken()} />

                <div className="flex flex-col gap-2">
                    <Label htmlFor="email">{text.auth.email}</Label>

                    <Input
                        id="email"
                        name="email"
                        type="email"
                        defaultValue={email}
                        autoComplete="username"
                        required
                    />

                    {errors?.email ? (
                        <p className="text-sm text-destructive">{errors.email}</p>
                    ) : null}
                </div>

                <div className="flex flex-col gap-2">
                    <Label htmlFor="password">{text.auth.new_password}</Label>

                    <Input
                        id="password"
                        name="password"
                        type="password"
                        autoComplete="new-password"
                        required
                        autoFocus
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

                <Button type="submit">{text.auth.submit_reset}</Button>
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
