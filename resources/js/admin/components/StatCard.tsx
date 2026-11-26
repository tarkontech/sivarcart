import { cn } from '@/lib/utils'

/**
 * One of the numbers of the dashboard.
 */
export function StatCard({
    label,
    value,
    hint,
    className,
}: {
    label: string
    value: number | string
    hint?: string
    className?: string
}) {
    return (
        <div
            className={cn(
                'flex flex-col gap-1 rounded-xl border border-border bg-card p-6 text-card-foreground shadow-sm',
                className,
            )}
        >
            <span className="text-sm text-muted-foreground">{label}</span>
            <span className="text-3xl font-semibold tracking-tight">{value}</span>
            {hint ? <span className="text-sm text-muted-foreground">{hint}</span> : null}
        </div>
    )
}
