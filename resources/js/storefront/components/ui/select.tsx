import { Select as SelectPrimitive } from '@base-ui/react/select'
import { CheckIcon, ChevronDownIcon } from 'lucide-react'
import type * as React from 'react'

import { cn } from '@storefront/lib/utils'

/**
 * The select of the panel.
 *
 * The list of the states of the store is a Base UI select, so it is
 * accessible with the keyboard and it works with the screen readers. Every
 * color is a semantic token.
 */
function Select(props: SelectPrimitive.Root.Props<string>) {
    return <SelectPrimitive.Root {...props} />
}

function SelectTrigger({
    className,
    children,
    ...props
}: React.ComponentProps<typeof SelectPrimitive.Trigger>) {
    return (
        <SelectPrimitive.Trigger
            data-slot="select-trigger"
            className={cn(
                'flex h-9 w-full items-center justify-between gap-2 rounded-md border border-input bg-transparent px-3 py-2 text-sm whitespace-nowrap shadow-xs transition-[color,box-shadow] outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:cursor-not-allowed disabled:opacity-50 data-[placeholder]:text-muted-foreground',
                className,
            )}
            {...props}
        >
            {children}
            <SelectPrimitive.Icon className="text-muted-foreground">
                <ChevronDownIcon className="size-4" />
            </SelectPrimitive.Icon>
        </SelectPrimitive.Trigger>
    )
}

function SelectValue({
    className,
    ...props
}: React.ComponentProps<typeof SelectPrimitive.Value>) {
    return (
        <SelectPrimitive.Value
            data-slot="select-value"
            className={cn('truncate', className)}
            {...props}
        />
    )
}

function SelectContent({
    className,
    children,
    ...props
}: React.ComponentProps<typeof SelectPrimitive.Popup>) {
    return (
        <SelectPrimitive.Portal>
            <SelectPrimitive.Positioner
                className="z-50"
                sideOffset={4}
                alignItemWithTrigger={false}
            >
                <SelectPrimitive.Popup
                    data-slot="select-content"
                    className={cn(
                        'max-h-(--available-height) min-w-32 origin-(--transform-origin) overflow-y-auto rounded-md border border-border bg-popover p-1 text-popover-foreground shadow-md transition-[transform,scale,opacity] data-[ending-style]:scale-95 data-[ending-style]:opacity-0 data-[starting-style]:scale-95 data-[starting-style]:opacity-0',
                        className,
                    )}
                    {...props}
                >
                    <SelectPrimitive.List>{children}</SelectPrimitive.List>
                </SelectPrimitive.Popup>
            </SelectPrimitive.Positioner>
        </SelectPrimitive.Portal>
    )
}

function SelectItem({
    className,
    children,
    ...props
}: React.ComponentProps<typeof SelectPrimitive.Item>) {
    return (
        <SelectPrimitive.Item
            data-slot="select-item"
            className={cn(
                'relative flex w-full cursor-default items-center gap-2 rounded-sm py-1.5 pr-8 pl-2 text-sm outline-none select-none data-[highlighted]:bg-accent data-[highlighted]:text-accent-foreground data-[disabled]:pointer-events-none data-[disabled]:opacity-50',
                className,
            )}
            {...props}
        >
            <SelectPrimitive.ItemText>{children}</SelectPrimitive.ItemText>
            <span className="absolute right-2 flex size-3.5 items-center justify-center">
                <SelectPrimitive.ItemIndicator>
                    <CheckIcon className="size-4" />
                </SelectPrimitive.ItemIndicator>
            </span>
        </SelectPrimitive.Item>
    )
}

export { Select, SelectTrigger, SelectValue, SelectContent, SelectItem }
