import { router } from '@inertiajs/react'
import { Trash2 } from 'lucide-react'

import { Button } from '@/components/ui/button'

/**
 * The button that takes something out of the store.
 *
 * The panel asks before it removes something, because a removal is not
 * something the staff can undo from the screen they are in.
 */
export function DeleteButton({
    url,
    label,
    confirm,
}: {
    url: string
    label: string
    confirm: string
}) {
    return (
        <Button
            type="button"
            variant="ghost"
            size="icon"
            title={label}
            aria-label={label}
            onClick={() => {
                if (!window.confirm(confirm)) {
                    return
                }

                router.delete(url, { preserveScroll: true })
            }}
        >
            <Trash2 />
        </Button>
    )
}
