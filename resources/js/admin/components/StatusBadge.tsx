import { Badge } from '@/components/ui/badge'
import type { State } from '@/types'

/**
 * The state of the store, shown with the tone the server sent.
 *
 * The panel does not decide how a state is read: the server sends the tone
 * and the panel only decides the color of that tone.
 */
export function StatusBadge({ state }: { state: State }) {
    return <Badge tone={state.tone}>{state.label}</Badge>
}
