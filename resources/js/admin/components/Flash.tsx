import { usePage } from '@inertiajs/react'
import { CircleCheck, CircleX } from 'lucide-react'

import { Alert } from '@/components/ui/alert'
import type { PanelSharedProps } from '@/types'

/**
 * The notices of the panel: what happened and what went wrong.
 *
 * The notices come from the server as flash data, and the errors come from
 * the validation of the server. Only the errors that belong to no field are
 * shown here: the ones of a field are shown under the field, and the form
 * that asked for them is the one that knows where they belong.
 */
export function Flash() {
    const page = usePage<PanelSharedProps>()
    const flash = page.flash as { notice?: unknown } | undefined
    const errors = page.props.errors as Record<string, string> | undefined

    const notice = typeof flash?.notice === 'string' ? flash.notice : null
    const failure = errors?.status ?? null

    if (!notice && !failure) {
        return null
    }

    return (
        <div className="flex flex-col gap-2">
            {notice ? (
                <Alert tone="success">
                    <CircleCheck />
                    <span>{notice}</span>
                </Alert>
            ) : null}

            {failure ? (
                <Alert tone="danger">
                    <CircleX />
                    <span>{failure}</span>
                </Alert>
            ) : null}
        </div>
    )
}
