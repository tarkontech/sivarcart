import { Head, usePage } from '@inertiajs/react'

import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { AuthLayout } from '@/layouts/AuthLayout'
import type { PanelSharedProps } from '@/types'

/**
 * The sign in screen of the panel.
 *
 * The form is a normal form, not one of Inertia, so the staff of the store
 * can sign in even when the script of the panel did not load.
 */
export default function Login({ email }: { email: string }) {
    const page = usePage<PanelSharedProps>()
    const { text } = page.props
    const errors = page.props.errors as Record<string, string> | undefined

    const token =
        document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? ''

    return (
        <AuthLayout>
            <Head title={text.login.title} />

            <Card>
                <CardHeader>
                    <CardTitle>{text.login.title}</CardTitle>
                </CardHeader>

                <CardContent>
                    <form method="POST" action={window.location.pathname} className="flex flex-col gap-4">
                        <input type="hidden" name="_token" value={token} />

                        <div className="flex flex-col gap-2">
                            <Label htmlFor="email">{text.login.email}</Label>
                            <Input
                                id="email"
                                name="email"
                                type="email"
                                defaultValue={email}
                                autoComplete="username"
                                autoFocus
                                required
                            />
                            {errors?.email ? (
                                <span className="text-sm text-destructive">{errors.email}</span>
                            ) : null}
                        </div>

                        <div className="flex flex-col gap-2">
                            <Label htmlFor="password">{text.login.password}</Label>
                            <Input
                                id="password"
                                name="password"
                                type="password"
                                autoComplete="current-password"
                                required
                            />
                        </div>

                        <label className="flex items-center gap-2 text-sm">
                            <input
                                type="checkbox"
                                name="remember"
                                value="1"
                                className="size-4 rounded border-input"
                            />
                            <span>{text.login.remember}</span>
                        </label>

                        <Button type="submit">{text.login.submit}</Button>
                    </form>
                </CardContent>
            </Card>
        </AuthLayout>
    )
}
