import { Head, useForm, usePage } from '@inertiajs/react'
import type { FormEvent } from 'react'

import { Field, FormActions } from '@/components/Form'
import { LinkButton } from '@/components/LinkButton'
import { PageHeader } from '@/components/PageHeader'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Checkbox } from '@/components/ui/checkbox'
import { Input } from '@/components/ui/input'
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select'
import { Textarea } from '@/components/ui/textarea'
import { PanelLayout } from '@/layouts/PanelLayout'
import type { PanelSharedProps, ShippingMethodItem, State } from '@/types'

/**
 * The form of a shipping method of the store.
 */
export default function ShippingMethodsForm({
    method,
    types,
    canUpdate,
}: {
    method: ShippingMethodItem | null
    types: State[]
    canUpdate: boolean
}) {
    const { nav, text } = usePage<PanelSharedProps>().props

    const base =
        nav.find((item) => item.key === 'shipping-methods')?.url ?? window.location.pathname

    const { data, setData, post, put, processing, errors } = useForm({
        name: method?.name ?? '',
        code: method?.code ?? '',
        type: method?.type ?? (types[0]?.value ?? 'flat_rate'),
        price: String(method?.price ?? '0'),
        min_order_amount: method?.min_order_amount === null || method === null ? '' : String(method.min_order_amount),
        max_order_amount: method?.max_order_amount === null || method === null ? '' : String(method.max_order_amount),
        estimated_days:
            method?.estimated_days === null || method === null ? '' : String(method.estimated_days),
        instructions: method?.instructions ?? '',
        position: String(method?.position ?? 0),
        is_active: method?.is_active ?? true,
    })

    const submit = (event: FormEvent) => {
        event.preventDefault()

        if (method === null) {
            post(base, { preserveScroll: true })

            return
        }

        put(`${base}/${method.uuid}`, { preserveScroll: true })
    }

    return (
        <PanelLayout>
            <Head title={text.shipping_methods.edit} />

            <div className="flex flex-col gap-4">
                <LinkButton href={base} variant="link" size="sm" className="w-fit p-0">
                    {text.shipping_methods.back}
                </LinkButton>

                <PageHeader title={method?.name || text.shipping_methods.new} />
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
                        <CardTitle>{text.shipping_methods.name}</CardTitle>
                    </CardHeader>

                    <CardContent className="flex flex-col gap-4">
                        <Field label={text.shipping_methods.name} htmlFor="name" error={errors.name}>
                            <Input
                                id="name"
                                name="name"
                                value={data.name}
                                disabled={!canUpdate}
                                onChange={(event) => setData('name', event.target.value)}
                            />
                        </Field>

                        <Field label={text.shipping_methods.code} htmlFor="code" error={errors.code}>
                            <Input
                                id="code"
                                name="code"
                                value={data.code}
                                disabled={!canUpdate}
                                onChange={(event) => setData('code', event.target.value)}
                            />
                        </Field>

                        <Field label={text.shipping_methods.type} htmlFor="type" error={errors.type}>
                            <Select
                                value={data.type}
                                disabled={!canUpdate}
                                onValueChange={(value) => setData('type', value ?? data.type)}
                            >
                                <SelectTrigger id="type">
                                    <SelectValue />
                                </SelectTrigger>

                                <SelectContent>
                                    {types.map((type) => (
                                        <SelectItem key={type.value} value={type.value}>
                                            {type.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </Field>

                        <div className="grid gap-4 sm:grid-cols-2">
                            <Field
                                label={text.shipping_methods.price}
                                htmlFor="price"
                                error={errors.price}
                            >
                                <Input
                                    id="price"
                                    name="price"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    value={data.price}
                                    disabled={!canUpdate}
                                    onChange={(event) => setData('price', event.target.value)}
                                />
                            </Field>

                            <Field
                                label={text.shipping_methods.estimated_days}
                                htmlFor="estimated_days"
                                error={errors.estimated_days}
                            >
                                <Input
                                    id="estimated_days"
                                    name="estimated_days"
                                    type="number"
                                    min="0"
                                    value={data.estimated_days}
                                    disabled={!canUpdate}
                                    onChange={(event) =>
                                        setData('estimated_days', event.target.value)
                                    }
                                />
                            </Field>
                        </div>

                        <div className="grid gap-4 sm:grid-cols-2">
                            <Field
                                label={text.shipping_methods.min_order_amount}
                                htmlFor="min_order_amount"
                                error={errors.min_order_amount}
                            >
                                <Input
                                    id="min_order_amount"
                                    name="min_order_amount"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    value={data.min_order_amount}
                                    disabled={!canUpdate}
                                    onChange={(event) =>
                                        setData('min_order_amount', event.target.value)
                                    }
                                />
                            </Field>

                            <Field
                                label={text.shipping_methods.max_order_amount}
                                htmlFor="max_order_amount"
                                error={errors.max_order_amount}
                            >
                                <Input
                                    id="max_order_amount"
                                    name="max_order_amount"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    value={data.max_order_amount}
                                    disabled={!canUpdate}
                                    onChange={(event) =>
                                        setData('max_order_amount', event.target.value)
                                    }
                                />
                            </Field>
                        </div>

                        <Field
                            label={text.shipping_methods.instructions}
                            htmlFor="instructions"
                            error={errors.instructions}
                        >
                            <Textarea
                                id="instructions"
                                name="instructions"
                                value={data.instructions}
                                disabled={!canUpdate}
                                onChange={(event) => setData('instructions', event.target.value)}
                            />
                        </Field>

                        <Field label={text.shipping_methods.position} htmlFor="position" error={errors.position}>
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
                            {text.shipping_methods.active}
                        </label>
                    </CardContent>
                </Card>

                {canUpdate ? (
                    <FormActions submit={text.shipping_methods.save} processing={processing}>
                        <LinkButton href={base} variant="outline">
                            {text.shipping_methods.cancel}
                        </LinkButton>
                    </FormActions>
                ) : null}
            </form>
        </PanelLayout>
    )
}
