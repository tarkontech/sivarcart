import { Head, router, useForm, usePage } from '@inertiajs/react'
import { useState } from 'react'

import { DeleteButton } from '@/components/DeleteButton'
import { Field, FormActions } from '@/components/Form'
import { PageHeader } from '@/components/PageHeader'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Checkbox } from '@/components/ui/checkbox'
import { Input } from '@/components/ui/input'
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table'
import { PanelLayout } from '@/layouts/PanelLayout'
import type { PanelSharedProps, RoleItem } from '@/types'

/**
 * The roles of the staff of the store.
 *
 * The roles come from the seeder of the package, and the permissions they
 * grant are the ones the middleware of the permissions and the routes of the
 * panel check. The staff writes here what every role grants.
 */
export default function RolesIndex({
    roles,
    permissions,
    canUpdate,
}: {
    roles: RoleItem[]
    permissions: string[]
    canUpdate: boolean
}) {
    const { nav, text } = usePage<PanelSharedProps>().props

    const base = nav.find((item) => item.key === 'roles')?.url ?? window.location.pathname

    const [creating, setCreating] = useState(false)

    const { data, setData, post, processing, errors, reset } = useForm({
        name: '',
        slug: '',
        description: '',
        permissions: [] as string[],
    })

    const submit = (event: React.FormEvent) => {
        event.preventDefault()

        post(base, { preserveScroll: true, onSuccess: () => reset() })
    }

    const toggle = (permission: string, checked: boolean) => {
        setData(
            'permissions',
            checked
                ? [...data.permissions, permission]
                : data.permissions.filter((value) => value !== permission),
        )
    }

    return (
        <PanelLayout>
            <Head title={text.roles.title} />

            <PageHeader
                title={text.roles.title}
                description={text.roles.description}
                actions={
                    canUpdate ? (
                        <Button
                            type="button"
                            size="sm"
                            variant={creating ? 'outline' : 'default'}
                            onClick={() => setCreating(!creating)}
                        >
                            {text.roles.new}
                        </Button>
                    ) : null
                }
            />

            {creating ? (
                <Card className="max-w-2xl">
                    <CardHeader>
                        <CardTitle>{text.roles.new}</CardTitle>
                    </CardHeader>

                    <CardContent>
                        <form onSubmit={submit} className="flex flex-col gap-4">
                            <Field label={text.roles.name} htmlFor="name" error={errors.name}>
                                <Input
                                    id="name"
                                    name="name"
                                    value={data.name}
                                    onChange={(event) => setData('name', event.target.value)}
                                />
                            </Field>

                            <Field label={text.roles.slug} htmlFor="slug" error={errors.slug}>
                                <Input
                                    id="slug"
                                    name="slug"
                                    value={data.slug}
                                    onChange={(event) => setData('slug', event.target.value)}
                                />
                            </Field>

                            <Field
                                label={text.roles.permissions}
                                error={errors.permissions}
                            >
                                <div className="flex flex-col gap-2">
                                    {permissions.map((permission) => (
                                        <label
                                            key={permission}
                                            className="flex items-center gap-2 text-sm"
                                        >
                                            <Checkbox
                                                checked={data.permissions.includes(permission)}
                                                onCheckedChange={(checked) =>
                                                    toggle(permission, checked === true)
                                                }
                                            />
                                            {permission}
                                        </label>
                                    ))}
                                </div>
                            </Field>

                            <FormActions submit={text.roles.create} processing={processing} />
                        </form>
                    </CardContent>
                </Card>
            ) : null}

            <Card>
                <CardContent className="flex flex-col gap-4">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>{text.roles.name}</TableHead>
                                <TableHead>{text.roles.slug}</TableHead>
                                <TableHead>{text.roles.permissions}</TableHead>
                                <TableHead>{text.roles.staff}</TableHead>
                                {canUpdate ? (
                                    <TableHead>{text.products.actions}</TableHead>
                                ) : null}
                            </TableRow>
                        </TableHeader>

                        <TableBody>
                            {roles.map((role) => (
                                <RoleRow
                                    key={role.slug}
                                    role={role}
                                    permissions={permissions}
                                    base={base}
                                    canUpdate={canUpdate}
                                />
                            ))}
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>
        </PanelLayout>
    )
}

/**
 * One role of the staff and the permissions it grants.
 *
 * The permissions of a role are a form of their own: the staff writes them
 * and saves them, and the panel asks the server to write them.
 */
function RoleRow({
    role,
    permissions,
    base,
    canUpdate,
}: {
    role: RoleItem
    permissions: string[]
    base: string
    canUpdate: boolean
}) {
    const { text } = usePage<PanelSharedProps>().props

    const [granted, setGranted] = useState<string[]>(role.granted)

    const dirty =
        granted.length !== role.granted.length ||
        granted.some((permission) => !role.granted.includes(permission))

    const save = () => {
        router.put(`${base}/${role.slug}`, { permissions: granted }, { preserveScroll: true })
    }

    const wildcards = role.permissions.filter((permission) => permission.endsWith('*'))

    return (
        <TableRow>
            <TableCell>
                <div className="flex flex-col gap-1">
                    <div className="flex items-center gap-2">
                        {role.name}

                        {role.slug === 'owner' ? (
                            <Badge tone="neutral">{text.roles.system}</Badge>
                        ) : null}
                    </div>

                    {wildcards.length > 0 ? (
                        <div className="flex flex-wrap gap-1">
                            {wildcards.map((wildcard) => (
                                <Badge key={wildcard} tone="warning">
                                    {wildcard}
                                </Badge>
                            ))}
                        </div>
                    ) : null}
                </div>
            </TableCell>

            <TableCell>
                <code className="rounded bg-muted px-1.5 py-0.5 text-xs">{role.slug}</code>
            </TableCell>

            <TableCell>
                <div className="flex flex-wrap items-center gap-3">
                    {permissions.map((permission) => (
                        <label key={permission} className="flex items-center gap-2 text-sm">
                            <Checkbox
                                checked={granted.includes(permission)}
                                disabled={!canUpdate}
                                onCheckedChange={(checked) =>
                                    setGranted((current) =>
                                        checked === true
                                            ? [...current, permission]
                                            : current.filter((value) => value !== permission),
                                    )
                                }
                            />
                            {permission}
                        </label>
                    ))}
                </div>
            </TableCell>

            <TableCell>{role.staff}</TableCell>

            {canUpdate ? (
                <TableCell>
                    <div className="flex items-center gap-1">
                        <Button
                            type="button"
                            size="sm"
                            variant="ghost"
                            disabled={!dirty}
                            onClick={save}
                        >
                            {text.roles.save}
                        </Button>

                        {role.slug !== 'owner' ? (
                            <DeleteButton
                                url={`${base}/${role.slug}`}
                                label={text.roles.remove}
                                confirm={text.roles.remove_confirm}
                            />
                        ) : null}
                    </div>
                </TableCell>
            ) : null}
        </TableRow>
    )
}
