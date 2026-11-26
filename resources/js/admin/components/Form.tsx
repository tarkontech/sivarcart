import type * as React from 'react'

import { Button } from '@/components/ui/button'
import { Label } from '@/components/ui/label'

/**
 * A field of a form of the panel.
 *
 * The error of the field is shown under it, so the staff knows what the
 * server did not accept without losing the place they were.
 */
export function Field({
    label,
    htmlFor,
    error,
    hint,
    className,
    children,
}: {
    label: string
    htmlFor?: string
    error?: string
    hint?: string
    className?: string
    children: React.ReactNode
}) {
    return (
        <div className={className ?? 'flex flex-col gap-2'}>
            <Label htmlFor={htmlFor}>{label}</Label>

            {children}

            {hint && !error ? (
                <span className="text-sm text-muted-foreground">{hint}</span>
            ) : null}

            {error ? <span className="text-sm text-destructive">{error}</span> : null}
        </div>
    )
}

/**
 * The buttons of a form of the panel.
 *
 * The button of the form is disabled while the form is being written, so the
 * staff does not send it twice.
 */
export function FormActions({
    submit,
    processing,
    children,
}: {
    submit: string
    processing: boolean
    children?: React.ReactNode
}) {
    return (
        <div className="flex items-center gap-2">
            <Button type="submit" disabled={processing}>
                {submit}
            </Button>

            {children}
        </div>
    )
}
