import { Moon, Sun } from 'lucide-react'
import { useEffect, useState } from 'react'

import { Button } from '@/components/ui/button'

const storageKey = 'sivarcart-admin-theme'

/**
 * The switch between the light theme and the dark one.
 *
 * The dark theme is a class in the document, so the panel reads it with the
 * variant of Tailwind. The choice of the staff is kept in the browser.
 */
export function ThemeToggle({
    light,
    dark,
}: {
    light: string
    dark: string
}) {
    const [isDark, setIsDark] = useState(false)

    useEffect(() => {
        const stored = window.localStorage.getItem(storageKey)

        const prefersDark =
            stored === 'dark' ||
            (stored === null &&
                window.matchMedia('(prefers-color-scheme: dark)').matches)

        setIsDark(prefersDark)
        document.documentElement.classList.toggle('dark', prefersDark)
    }, [])

    const toggle = () => {
        const next = !isDark

        setIsDark(next)
        document.documentElement.classList.toggle('dark', next)
        window.localStorage.setItem(storageKey, next ? 'dark' : 'light')
    }

    return (
        <Button
            type="button"
            variant="ghost"
            size="icon"
            onClick={toggle}
            aria-label={isDark ? light : dark}
        >
            {isDark ? <Sun className="size-4" /> : <Moon className="size-4" />}
        </Button>
    )
}
