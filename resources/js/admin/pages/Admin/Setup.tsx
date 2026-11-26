import { Head, usePage } from '@inertiajs/react'

import { Card, CardContent } from '@/components/ui/card'
import { AuthLayout } from '@/layouts/AuthLayout'
import type { PanelSharedProps } from '@/types'

/**
 * The screen that explains what the application has to do for the panel to
 * know its staff.
 *
 * The roles of the store live in a pivot that points at the users of the host
 * application, so the model of the user has to carry the trait of the roles.
 * The package cannot add the trait by itself, so this screen says what to do.
 */
export default function Setup({ model }: { model: string; signedIn: boolean }) {
    const { text } = usePage<PanelSharedProps>().props

    const name = model.split('\\').pop() ?? model

    return (
        <AuthLayout>
            <Head title={text.setup.title} />

            <Card>
                <CardContent className="flex flex-col gap-4 text-sm">
                    <p className="text-muted-foreground">{text.setup.body}</p>

                    <pre className="overflow-x-auto rounded-md bg-muted p-3 text-xs">
                        <code>{model}</code>
                    </pre>

                    <p className="text-muted-foreground">{text.setup.trait}</p>

                    <pre className="overflow-x-auto rounded-md bg-muted p-3 text-xs">
                        <code>{`use SivarcartCore\\SivarcartCore\\Models\\Concerns\\HasStoreRoles;

class ${name} extends Authenticatable
{
    use HasStoreRoles;
}`}</code>
                    </pre>

                    <p className="text-muted-foreground">{text.setup.roles}</p>

                    <pre className="overflow-x-auto rounded-md bg-muted p-3 text-xs">
                        <code>php artisan db:seed --class="SivarcartCore\SivarcartCore\Database\Seeders\StoreRoleSeeder"</code>
                    </pre>
                </CardContent>
            </Card>
        </AuthLayout>
    )
}
