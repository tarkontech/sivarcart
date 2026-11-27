import { Head, Link, usePage } from '@inertiajs/react'

import { Button } from '@storefront/components/ui/button'
import { Input } from '@storefront/components/ui/input'
import { Label } from '@storefront/components/ui/label'
import { storePath } from '@storefront/lib/url'
import { AccountLayout } from '@storefront/layouts/AccountLayout'
import type { StorefrontSharedProps } from '@storefront/types'

/**
 * The screen that asks for the link that resets the password.
 *
 * La respuesta es la misma cuando la cuenta no existe, así que la tienda no
 * dice quién tiene una cuenta.
 */
export default function ForgotPassword() {
    const { prefix, text } = usePage<StorefrontSharedProps>().props

    const errors = usePage<StorefrontSharedProps>().props.errors as
        | Record<string, string>
        | undefined

    return (
        <AccountLayout>
            <Head title={text.auth.forgot_title} />

            <div className="flex flex-col gap-2">
                <h1 className="text-2xl font-semibold tracking-tight">
                    {text.auth.forgot_title}
                </h1>

                <p className="text-sm text-muted-foreground">{text.auth.forgot_body}</p>
            </div>

            <form
                method="POST"
                action={storePath(prefix, 'cuenta/recuperar')}
                className="flex flex-col gap-4"
            >
                <input type="hidden" name="_token" value={csrfToken()} />

                <div className="flex flex-col gap-2">
                    <Label htmlFor="email">{text.auth.email}</Label>

                    <Input id="email" name="email" type="email" autoComplete="username" required autoFocus />

                    {errors?.email ? (
                        <p className="text-sm text-destructive">{errors.email}</p>
                    ) : null}
                </div>

                <Button type="submit">{text.auth.send_link}</Button>

                <Link
                    href={storePath(prefix, 'cuenta/entrar')}
                    className="text-sm text-muted-foreground hover:text-foreground hover:underline"
                >
                    ← {text.auth.submit_login}
                </Link>
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
