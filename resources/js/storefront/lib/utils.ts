import { clsx, type ClassValue } from 'clsx'
import { twMerge } from 'tailwind-merge'

/**
 * The classes of an element.
 *
 * The classes of Tailwind that fight each other are resolved, so the last one
 * wins, and the conditional ones are joined.
 */
export function cn(...inputs: ClassValue[]): string {
    return twMerge(clsx(inputs))
}
