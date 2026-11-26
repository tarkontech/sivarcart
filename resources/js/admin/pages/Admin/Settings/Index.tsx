import { Head, useForm, usePage } from '@inertiajs/react'
import type { FormEvent } from 'react'

import { PageHeader } from '@/components/PageHeader'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { PanelLayout } from '@/layouts/PanelLayout'
import type { PanelSharedProps, StoreSettings } from '@/types'

/**
 * The settings of the store.
 *
 * The staff of the store changes the name of the store, its tax id and the
 * ways it reaches its buyers. The settings are written by the service of the
 * store, the same one the rest of the package reads.
 */
export default function SettingsIndex({ settings }: { settings: StoreSettings }) {
    const { nav, text } = usePage<PanelSharedProps>().props

    const { data, setData, put, processing, errors } = useForm({
        name: settings.name,
        legal_name: settings.legal_name,
        tax_id: settings.tax_id,
        email: settings.email,
        phone: settings.phone,
    })

    const url = nav.find((item) => item.key === 'settings')?.url ?? window.location.pathname

    const submit = (event: FormEvent) => {
        event.preventDefault()

        put(url, { preserveScroll: true })
    }

    return (
        <PanelLayout>
            <Head title={text.settings.title} />

            <PageHeader title={text.settings.title} description={text.settings.description} />

            <Card className="max-w-2xl">
                <CardHeader>
                    <CardTitle>{text.settings.title}</CardTitle>
                </CardHeader>

                <CardContent>
                    <form onSubmit={submit} className="flex flex-col gap-4">
                        <div className="flex flex-col gap-2">
                            <Label htmlFor="name">{text.settings.name}</Label>
                            <Input
                                id="name"
                                name="name"
                                value={data.name}
                                onChange={(event) => setData('name', event.target.value)}
                            />
                            {errors.name ? (
                                <span className="text-sm text-destructive">{errors.name}</span>
                            ) : null}
                        </div>

                        <div className="flex flex-col gap-2">
                            <Label htmlFor="legal_name">{text.settings.legal_name}</Label>
                            <Input
                                id="legal_name"
                                name="legal_name"
                                value={data.legal_name}
                                onChange={(event) => setData('legal_name', event.target.value)}
                            />
                            {errors.legal_name ? (
                                <span className="text-sm text-destructive">
                                    {errors.legal_name}
                                </span>
                            ) : null}
                        </div>

                        <div className="flex flex-col gap-2">
                            <Label htmlFor="tax_id">{text.settings.tax_id}</Label>
                            <Input
                                id="tax_id"
                                name="tax_id"
                                value={data.tax_id}
                                onChange={(event) => setData('tax_id', event.target.value)}
                            />
                            {errors.tax_id ? (
                                <span className="text-sm text-destructive">{errors.tax_id}</span>
                            ) : null}
                        </div>

                        <div className="flex flex-col gap-2">
                            <Label htmlFor="email">{text.settings.email}</Label>
                            <Input
                                id="email"
                                name="email"
                                type="email"
                                value={data.email}
                                onChange={(event) => setData('email', event.target.value)}
                            />
                            {errors.email ? (
                                <span className="text-sm text-destructive">{errors.email}</span>
                            ) : null}
                        </div>

                        <div className="flex flex-col gap-2">
                            <Label htmlFor="phone">{text.settings.phone}</Label>
                            <Input
                                id="phone"
                                name="phone"
                                value={data.phone}
                                onChange={(event) => setData('phone', event.target.value)}
                            />
                            {errors.phone ? (
                                <span className="text-sm text-destructive">{errors.phone}</span>
                            ) : null}
                        </div>

                        <div>
                            <Button type="submit" disabled={processing}>
                                {text.settings.save}
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </PanelLayout>
    )
}
