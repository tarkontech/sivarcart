import { Head, useForm, usePage } from '@inertiajs/react'
import type { FormEvent } from 'react'

import { Field, FormActions } from '@/components/Form'
import { LinkButton } from '@/components/LinkButton'
import { PageHeader } from '@/components/PageHeader'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Checkbox } from '@/components/ui/checkbox'
import { Input } from '@/components/ui/input'
import { Textarea } from '@/components/ui/textarea'
import { PanelLayout } from '@/layouts/PanelLayout'
import type { BrandItem, PanelSharedProps } from '@/types'

/**
 * The form of a brand of the catalog.
 */
export default function BrandsForm({
    brand,
    canUpdate,
}: {
    brand: BrandItem | null
    canUpdate: boolean
}) {
    const { nav, text } = usePage<PanelSharedProps>().props

    const base = nav.find((item) => item.key === 'brands')?.url ?? window.location.pathname

    const { data, setData, post, put, processing, errors } = useForm({
        name: brand?.name ?? '',
        slug: brand?.slug ?? '',
        description: brand?.description ?? '',
        position: String(brand?.position ?? 0),
        is_active: brand?.is_active ?? true,
    })

    const submit = (event: FormEvent) => {
        event.preventDefault()

        if (brand === null) {
            post(base, { preserveScroll: true })

            return
        }

        put(`${base}/${brand.id}`, { preserveScroll: true })
    }

    return (
        <PanelLayout>
            <Head title={text.brands.edit} />

            <div className="flex flex-col gap-4">
                <LinkButton href={base} variant="link" size="sm" className="w-fit p-0">
                    {text.brands.back}
                </LinkButton>

                <PageHeader title={brand?.name || text.brands.new} />
            </div>

            {!canUpdate ? (
                <Card>
                    <CardContent>
                        <p className="text-sm text-muted-foreground">
                            {text.products.read_only}
                        </p>
                    </CardContent>
                </Card>
            ) : null}

            <form onSubmit={submit} className="flex flex-col gap-6">
                <Card className="max-w-2xl">
                    <CardHeader>
                        <CardTitle>{text.brands.name}</CardTitle>
                    </CardHeader>

                    <CardContent className="flex flex-col gap-4">
                        <Field label={text.brands.name} htmlFor="name" error={errors.name}>
                            <Input
                                id="name"
                                name="name"
                                value={data.name}
                                disabled={!canUpdate}
                                onChange={(event) => setData('name', event.target.value)}
                            />
                        </Field>

                        <Field label={text.brands.slug} htmlFor="slug" error={errors.slug}>
                            <Input
                                id="slug"
                                name="slug"
                                value={data.slug}
                                disabled={!canUpdate}
                                onChange={(event) => setData('slug', event.target.value)}
                            />
                        </Field>

                        <Field
                            label={text.brands.description}
                            htmlFor="description"
                            error={errors.description}
                        >
                            <Textarea
                                id="description"
                                name="description"
                                value={data.description}
                                disabled={!canUpdate}
                                onChange={(event) => setData('description', event.target.value)}
                            />
                        </Field>

                        <Field label={text.brands.position} htmlFor="position" error={errors.position}>
                            <Input
                                id="position"
                                name="position"
                                type="number"
                                min="0"
                                value={data.position}
                                disabled={!canUpdate}
                                onChange={(event) => setData('position', event.target.value)}
                            />
                        </Field>

                        <label className="flex items-center gap-2 text-sm">
                            <Checkbox
                                checked={data.is_active}
                                disabled={!canUpdate}
                                onCheckedChange={(checked) => setData('is_active', checked === true)}
                            />
                            {text.brands.active}
                        </label>
                    </CardContent>
                </Card>

                {canUpdate ? (
                    <FormActions submit={text.brands.save} processing={processing}>
                        <LinkButton href={base} variant="outline">
                            {text.brands.cancel}
                        </LinkButton>
                    </FormActions>
                ) : null}
            </form>
        </PanelLayout>
    )
}
