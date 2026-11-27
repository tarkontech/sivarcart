import { Head, Link, usePage } from '@inertiajs/react'

import { Button } from '@storefront/components/ui/button'
import { Input } from '@storefront/components/ui/input'
import { Label } from '@storefront/components/ui/label'
import { storePath } from '@storefront/lib/url'
import { AccountLayout } from '@storefront/layouts/AccountLayout'
import type { StorefrontSharedProps } from '@storefront/types'

/**
 * The screen of entry of the buyer.
 *
 * The form es un formulario normal, no uno de Inertia, así que el comprador
 * puede entrar aunque el script de la tienda no haya cargado.
 */
export default function Login({
    email,
    redirect,
}: {
    email: string
    redirect: string | null
}) {
    const { prefix, text } = usePage<StorefrontSharedProps>().props

    const errors = usePage<StorefrontSharedProps>().props.errors as
        | Record<string, string>
        | undefined

    return (
        <AccountLayout>
            <Head title={text.auth.login_title} />

            <div className="flex flex-col gap-2">
                <h1 className="text-2xl font-semibold tracking-tight">
                    {text.auth.login_title}
                </h1>

                <p className="text-sm text-muted-foreground">{text.auth.login_body}</p>
            </div>

            <form
                method="POST"
                action={storePath(prefix, 'cuenta/entrar')}
                className="flex flex-col gap-4"
            >
                <input type="hidden" name="_token" value={csrfToken()} />

                {redirect ? <input type="hidden" name="redirect" value={redirect} /> : null}

                <div className="flex flex-col gap-2">
                    <Label htmlFor="email">{text.auth.email}</Label>

                    <Input
                        id="email"
                        name="email"
                        type="email"
                        defaultValue={email}
                        autoComplete="username"
                        required
                        autoFocus
                    />

                    {errors?.email ? (
                        <p className="text-sm text-destructive">{errors.email}</p>
                    ) : null}
                </div>

                <div className="flex flex-col gap-2">
                    <Label htmlFor="password">{text.auth.password}</Label>

                    <Input
                        id="password"
                        name="password"
                        type="password"
                        autoComplete="current-password"
                        required
                    />
                </div>

                <Button type="submit">{text.auth.submit_login}</Button>

                <div className="flex items-center justify-between text-sm">
                    <Link
                        href={storePath(prefix, 'cuenta/recuperar')}
                        className="text-muted-foreground hover:text-foreground hover:underline"
                    >
                        {text.auth.forgot}
                    </Link>

                    <Link
                        href={storePath(prefix, 'cuenta/registro')}
                        className="text-primary hover:underline"
                    >
                        {text.auth.no_account}
                    </Link>
                </div>
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
