import { createInertiaApp } from '@inertiajs/react'
import type { ComponentType } from 'react'
import { createRoot } from 'react-dom/client'

import type { PanelSharedProps } from '@/types'

/**
 * The pages of the panel.
 *
 * The glob only sees the files of the package, so the application that
 * installs the package never captures the pages of the panel in the Inertia
 * of its own. A merchant that builds its own bundle resolves its own pages
 * first, and the panel shows them.
 */
const pages = import.meta.glob('./pages/**/*.tsx')

/**
 * The panel of the administration of SivarCart.
 *
 * The panel mounts in its own element, so an application that already has an
 * Inertia of its own keeps it untouched.
 */
export function createPanelApp(): Promise<void> {
    return createInertiaApp<PanelSharedProps>({
        id: 'sivarcart-admin',

        title: (title, page) => {
            const store = (page.props as unknown as PanelSharedProps).store
            const name = store?.name

            if (title && name) {
                return `${title} · ${name}`
            }

            return title ?? name ?? 'SivarCart'
        },

        resolve: (name) => {
            const page = pages[`./pages/${name}.tsx`]

            if (!page) {
                throw new Error(
                    `The page [${name}] is not in the bundle of the panel of SivarCart.`,
                )
            }

            return page() as Promise<ComponentType>
        },

        setup({ el, App, props }) {
            if (!el) {
                throw new Error('The panel of SivarCart did not find its element.')
            }

            createRoot(el).render(<App {...props} />)
        },
    })
}
