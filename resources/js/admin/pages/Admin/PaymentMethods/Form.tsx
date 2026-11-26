import { Head, useForm, usePage } from '@inertiajs/react'
import type { FormEvent } from 'react'

import { Field, FormActions } from '@/components/Form'
import { LinkButton } from '@/components/LinkButton'
import { PageHeader } from '@/components/PageHeader'
import { Button } from '@/components/ui/button'
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
import type { CredentialPair, PanelSharedProps, PaymentMethodItem, State } from '@/types'

/**
 * The form of a payment method of the store.
 *
 * The credentials of a method are written here and never come back: the
 * panel shows the keys the method has, and the staff writes the values again
 * when they want to change them.
 */
export default function PaymentMethodsForm({
    method,
    credentials,
    types,
    canUpdate,
}: {
    method: PaymentMethodItem | null
    credentials: CredentialPair[]
    types: State[]
    canUpdate: boolean
}) {
    const { nav, text } = usePage<PanelSharedProps>().props

    const base =
        nav.find((item) => item.key === 'payment-methods')?.url ?? window.location.pathname

    const { data, setData, post, put, processing, errors } = useForm({
        name: method?.name ?? '',
        code: method?.code ?? '',
        type: method?.type ?? 'offline',
        driver: method?.driver ?? '',
        instructions: method?.instructions ?? '',
        icon: method?.icon ?? '',
        position: String(method?.position ?? 0),
        is_active: method?.is_active ?? true,
        credentials,
    })

    const isOnline = data.type === 'online'

    const submit = (event: FormEvent) => {
        event.preventDefault()

        if (method === null) {
            post(base, { preserveScroll: true })

            return
        }

        put(`${base}/${method.uuid}`, { preserveScroll: true })
    }

    const updateCredential = (index: number, field: keyof CredentialPair, value: string) => {
        setData(
            'credentials',
            data.credentials.map((pair, position) =>
                position === index ? { ...pair, [field]: value } : pair,
            ),
        )
    }

    const addCredential = () => {
        setData('credentials', [...data.credentials, { key: '', value: '' }])
    }

    const removeCredential = (index: number) => {
        setData(
            'credentials',
            data.credentials.filter((_, position) => position !== index),
        )
    }

    return (
        <PanelLayout>
            <Head title={text.payment_methods.edit} />

            <div className="flex flex-col gap-4">
                <LinkButton href={base} variant="link" size="sm" className="w-fit p-0">
                    {text.payment_methods.back}
                </LinkButton>

                <PageHeader title={method?.name || text.payment_methods.new} />
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
                        <CardTitle>{text.payment_methods.name}</CardTitle>
                    </CardHeader>

                    <CardContent className="flex flex-col gap-4">
                        <Field label={text.payment_methods.name} htmlFor="name" error={errors.name}>
                            <Input
                                id="name"
                                name="name"
                                value={data.name}
                                disabled={!canUpdate}
                                onChange={(event) => setData('name', event.target.value)}
                            />
                        </Field>

                        <Field label={text.payment_methods.code} htmlFor="code" error={errors.code}>
                            <Input
                                id="code"
                                name="code"
                                value={data.code}
                                disabled={!canUpdate}
                                onChange={(event) => setData('code', event.target.value)}
                            />
                        </Field>

                        <Field label={text.payment_methods.type} htmlFor="type" error={errors.type}>
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

                        {isOnline ? (
                            <Field
                                label={text.payment_methods.driver}
                                htmlFor="driver"
                                error={errors.driver}
                            >
                                <Input
                                    id="driver"
                                    name="driver"
                                    value={data.driver}
                                    disabled={!canUpdate}
                                    onChange={(event) => setData('driver', event.target.value)}
                                />
                            </Field>
                        ) : null}

                        <Field
                            label={text.payment_methods.instructions}
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

                        <Field label={text.payment_methods.icon} htmlFor="icon" error={errors.icon}>
                            <Input
                                id="icon"
                                name="icon"
                                value={data.icon}
                                disabled={!canUpdate}
                                onChange={(event) => setData('icon', event.target.value)}
                            />
                        </Field>

                        <Field label={text.payment_methods.position} htmlFor="position" error={errors.position}>
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
                            {text.payment_methods.active}
                        </label>
                    </CardContent>
                </Card>

                {isOnline ? (
                    <Card className="max-w-2xl">
                        <CardHeader>
                            <CardTitle>{text.payment_methods.credentials}</CardTitle>
                        </CardHeader>

                        <CardContent className="flex flex-col gap-4">
                            <p className="text-sm text-muted-foreground">
                                {text.payment_methods.credentials_hint}
                            </p>

                            {data.credentials.map((pair, index) => (
                                <div
                                    key={`credential-${index}`}
                                    className="grid gap-2 sm:grid-cols-[1fr_1fr_auto] sm:items-end"
                                >
                                    <Field
                                        label={text.payment_methods.credentials}
                                        htmlFor={`credential-key-${index}`}
                                        error={errors[`credentials.${index}.key`]}
                                    >
                                        <Input
                                            id={`credential-key-${index}`}
                                            value={pair.key}
                                            disabled={!canUpdate}
                                            onChange={(event) =>
                                                updateCredential(index, 'key', event.target.value)
                                            }
                                        />
                                    </Field>

                                    <Field
                                        label={text.payment_methods.credentials}
                                        htmlFor={`credential-value-${index}`}
                                        error={errors[`credentials.${index}.value`]}
                                    >
                                        <Input
                                            id={`credential-value-${index}`}
                                            type="password"
                                            value={pair.value}
                                            disabled={!canUpdate}
                                            onChange={(event) =>
                                                updateCredential(index, 'value', event.target.value)
                                            }
                                        />
                                    </Field>

                                    {canUpdate ? (
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="sm"
                                            className="text-destructive"
                                            onClick={() => removeCredential(index)}
                                        >
                                            {text.payment_methods.remove_credential}
                                        </Button>
                                    ) : null}
                                </div>
                            ))}

                            {canUpdate ? (
                                <div>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="sm"
                                        onClick={addCredential}
                                    >
                                        {text.payment_methods.add_credential}
                                    </Button>
                                </div>
                            ) : null}
                        </CardContent>
                    </Card>
                ) : null}

                {canUpdate ? (
                    <FormActions submit={text.payment_methods.save} processing={processing}>
                        <LinkButton href={base} variant="outline">
                            {text.payment_methods.cancel}
                        </LinkButton>
                    </FormActions>
                ) : null}
            </form>
        </PanelLayout>
    )
}
