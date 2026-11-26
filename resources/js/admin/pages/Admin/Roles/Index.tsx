import { Head, usePage } from '@inertiajs/react'

import { PageHeader } from '@/components/PageHeader'
import { Badge } from '@/components/ui/badge'
import { Card, CardContent } from '@/components/ui/card'
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
 * panel check.
 */
export default function RolesIndex({ roles }: { roles: RoleItem[] }) {
    const { text } = usePage<PanelSharedProps>().props

    return (
        <PanelLayout>
            <Head title={text.roles.title} />

            <PageHeader title={text.roles.title} description={text.roles.description} />

            <Card>
                <CardContent>
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>{text.roles.name}</TableHead>
                                <TableHead>{text.roles.slug}</TableHead>
                                <TableHead>{text.roles.permissions}</TableHead>
                                <TableHead>{text.roles.staff}</TableHead>
                            </TableRow>
                        </TableHeader>

                        <TableBody>
                            {roles.map((role) => (
                                <TableRow key={role.slug}>
                                    <TableCell>{role.name}</TableCell>
                                    <TableCell>
                                        <code className="rounded bg-muted px-1.5 py-0.5 text-xs">
                                            {role.slug}
                                        </code>
                                    </TableCell>
                                    <TableCell>
                                        <div className="flex flex-wrap gap-1">
                                            {role.permissions.map((permission) => (
                                                <Badge key={permission}>{permission}</Badge>
                                            ))}
                                        </div>
                                    </TableCell>
                                    <TableCell>{role.staff}</TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>
        </PanelLayout>
    )
}
