import type * as React from 'react'

import { Flash } from '@/components/Flash'

/**
 * The frame of the screens of the panel that are not the panel itself: the
 * sign in screen and the screen that explains what the application has to do.
 */
export function AuthLayout({ children }: { children: React.ReactNode }) {
    return (
        <div className="flex min-h-screen items-center justify-center bg-background p-6 text-foreground">
            <main className="flex w-full max-w-md flex-col gap-6">
                <Flash />
                {children}
            </main>
        </div>
    )
}
