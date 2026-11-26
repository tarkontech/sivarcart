import type { ComponentType } from 'react'

import { widgets } from './widgets'

/**
 * One widget of the dashboard.
 *
 * A widget is a component of the bundle of the panel, and the data it shows
 * comes from the server. The dashboard does not know anything about the
 * widget: it asks the bundle for the component of its name.
 */
export function WidgetSlot({
    name,
    props,
}: {
    name: string
    props: Record<string, unknown>
}) {
    const Component = widgets[name] as ComponentType<Record<string, unknown>> | undefined

    if (!Component) {
        return null
    }

    return <Component {...props} />
}
