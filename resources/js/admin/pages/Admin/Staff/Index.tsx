import { Head, router, useForm, usePage } from '@inertiajs/react'
import { useState } from 'react'

import { DeleteButton } from '@/components/DeleteButton'
import { EmptyState } from '@/components/EmptyState'
import { Field, FormActions } from '@/components/Form'
import { PageHeader } from '@/components/PageHeader'
import { Pagination } from '@/components/Pagination'
import { SearchInput } from '@/components/SearchInput'
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
import type { Paginated, PanelSharedProps, RoleItem, StaffMember } from '@/types'

/**
 * The staff of the store.
 *
 * The staff are the users of the application, so the panel writes the roles
 * of the store on them and not the users themselves: "dar de baja" a member
 * of the staff takes the roles away, so they stop seeing the panel.
 */
export default function StaffIndex({
    staff,
    roles,
    filters,
    canUpdate,
}: {
    staff: Paginated<StaffMember>
    roles: RoleItem[]
    filters: { q: string }
    canUpdate: boolean
}) {
    const { nav, text } = usePage<PanelSharedProps>().props

    const base = nav.find((item) => item.key === 'staff')?.url ?? window.location.pathname

    const [creating, setCreating] = useState(false)

    const { data, setData, post, processing, errors, reset } = useForm({
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
        roles: [] as string[],
    })

    const submit = (event: React.FormEvent) => {
        event.preventDefault()

        post(base, { preserveScroll: true, onSuccess: () => reset() })
    }

    const toggleRole = (slug: string, checked: boolean) => {
        setData(
            'roles',
            checked ? [...data.roles, slug] : data.roles.filter((role) => role !== slug),
        )
    }

    const saveRoles = (member: StaffMember, slugs: string[]) => {
        router.put(
            `${base}/${member.id}`,
            { roles: slugs },
            { preserveScroll: true },
        )
    }

    return (
        <PanelLayout>
            <Head title={text.staff.title} />

            <PageHeader
                title={text.staff.title}
                description={text.staff.description}
                actions={
                    canUpdate ? (
                        <Button
                            type="button"
                            size="sm"
                            variant={creating ? 'outline' : 'default'}
                            onClick={() => setCreating(!creating)}
                        >
                            {text.staff.new}
                        </Button>
                    ) : null
                }
            />

            <div className="min-w-64 max-w-md">
                <SearchInput
                    value={filters.q}
                    placeholder={text.staff.search}
                    only={['staff']}
                />
            </div>

            {creating ? (
                <Card className="max-w-2xl">
                    <CardHeader>
                        <CardTitle>{text.staff.new}</CardTitle>
                    </CardHeader>

                    <CardContent>
                        <form onSubmit={submit} className="flex flex-col gap-4">
                            <Field label={text.staff.name} htmlFor="name" error={errors.name}>
                                <Input
                                    id="name"
                                    name="name"
                                    value={data.name}
                                    onChange={(event) => setData('name', event.target.value)}
                                />
                            </Field>

                            <Field label={text.staff.email} htmlFor="email" error={errors.email}>
                                <Input
                                    id="email"
                                    name="email"
                                    type="email"
                                    value={data.email}
                                    onChange={(event) => setData('email', event.target.value)}
                                />
                            </Field>

                            <Field
                                label={text.staff.password}
                                htmlFor="password"
                                error={errors.password}
                            >
                                <Input
                                    id="password"
                                    name="password"
                                    type="password"
                                    value={data.password}
                                    onChange={(event) => setData('password', event.target.value)}
                                />
                            </Field>

                            <Field
                                label={text.staff.password_confirmation}
                                htmlFor="password_confirmation"
                                error={errors.password_confirmation}
                            >
                                <Input
                                    id="password_confirmation"
                                    name="password_confirmation"
                                    type="password"
                                    value={data.password_confirmation}
                                    onChange={(event) =>
                                        setData('password_confirmation', event.target.value)
                                    }
                                />
                            </Field>

                            <Field label={text.staff.roles} error={errors.roles}>
                                <div className="flex flex-col gap-2">
                                    {roles.map((role) => (
                                        <label
                                            key={role.slug}
                                            className="flex items-center gap-2 text-sm"
                                        >
                                            <Checkbox
                                                checked={data.roles.includes(role.slug)}
                                                onCheckedChange={(checked) =>
                                                    toggleRole(role.slug, checked === true)
                                                }
                                            />
                                            {role.name}
                                        </label>
                                    ))}
                                </div>
                            </Field>

                            <FormActions submit={text.staff.create} processing={processing} />
                        </form>
                    </CardContent>
                </Card>
            ) : null}

            <Card>
                <CardContent className="flex flex-col gap-4">
                    {staff.data.length === 0 ? (
                        <EmptyState>{text.staff.empty}</EmptyState>
                    ) : (
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>{text.staff.name}</TableHead>
                                    <TableHead>{text.staff.email}</TableHead>
                                    <TableHead>{text.staff.roles}</TableHead>
                                    {canUpdate ? (
                                        <TableHead>{text.staff.actions}</TableHead>
                                    ) : null}
                                </TableRow>
                            </TableHeader>

                            <TableBody>
                                {staff.data.map((member) => (
                                    <StaffRow
                                        key={member.id}
                                        member={member}
                                        roles={roles}
                                        base={base}
                                        canUpdate={canUpdate}
                                        onSave={(slugs) => saveRoles(member, slugs)}
                                    />
                                ))}
                            </TableBody>
                        </Table>
                    )}

                    <Pagination meta={staff.meta} only={['staff']} />
                </CardContent>
            </Card>
        </PanelLayout>
    )
}

/**
 * One member of the staff and the roles it holds.
 *
 * The roles of a member are a form of their own: the staff writes them and
 * saves them, and the panel asks the server to write them.
 */
function StaffRow({
    member,
    roles,
    base,
    canUpdate,
    onSave,
}: {
    member: StaffMember
    roles: RoleItem[]
    base: string
    canUpdate: boolean
    onSave: (slugs: string[]) => void
}) {
    const { text } = usePage<PanelSharedProps>().props

    const [slugs, setSlugs] = useState<string[]>(member.roles)

    const dirty =
        slugs.length !== member.roles.length ||
        slugs.some((slug) => !member.roles.includes(slug))

    return (
        <TableRow>
            <TableCell>{member.name ?? '—'}</TableCell>
            <TableCell>{member.email ?? '—'}</TableCell>
            <TableCell>
                <div className="flex flex-wrap items-center gap-3">
                    {roles.map((role) => (
                        <label key={role.slug} className="flex items-center gap-2 text-sm">
                            <Checkbox
                                checked={slugs.includes(role.slug)}
                                disabled={!canUpdate}
                                onCheckedChange={(checked) =>
                                    setSlugs((current) =>
                                        checked === true
                                            ? [...current, role.slug]
                                            : current.filter((slug) => slug !== role.slug),
                                    )
                                }
                            />
                            {role.name}
                        </label>
                    ))}

                    {roles.length === 0 ? (
                        <Badge tone="danger">{text.staff.roles}</Badge>
                    ) : null}
                </div>
            </TableCell>

            {canUpdate ? (
                <TableCell>
                    <div className="flex items-center gap-1">
                        <Button
                            type="button"
                            size="sm"
                            variant="ghost"
                            disabled={!dirty}
                            onClick={() => onSave(slugs)}
                        >
                            {text.staff.save_roles}
                        </Button>

                        <DeleteButton
                            url={`${base}/${member.id}`}
                            label={text.staff.remove}
                            confirm={text.staff.remove_confirm}
                        />
                    </div>
                </TableCell>
            ) : null}
        </TableRow>
    )
}
