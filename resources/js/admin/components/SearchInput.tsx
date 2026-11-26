import { router } from '@inertiajs/react'
import { useEffect, useState } from 'react'

import { Input } from '@/components/ui/input'
import { currentQuery } from '@/lib/query'

/**
 * The search of a list of the panel.
 *
 * The search travels in the address, so the server answers with the list that
 * matches and the panel asks only for that list.
 */
export function SearchInput({
    value,
    placeholder,
    only,
    params = {},
}: {
    value: string
    placeholder: string
    only: string[]
    params?: Record<string, string>
}) {
    const [term, setTerm] = useState(value)

    useEffect(() => {
        setTerm(value)
    }, [value])

    useEffect(() => {
        if (term === value) {
            return
        }

        const timer = window.setTimeout(() => {
            router.get(
                window.location.pathname,
                { ...currentQuery(), ...params, q: term, page: undefined },
                { preserveState: true, preserveScroll: true, only },
            )
        }, 300)

        return () => window.clearTimeout(timer)
    }, [term, value, only, params, router])

    return (
        <Input
            type="search"
            value={term}
            placeholder={placeholder}
            aria-label={placeholder}
            onChange={(event) => setTerm(event.target.value)}
        />
    )
}
