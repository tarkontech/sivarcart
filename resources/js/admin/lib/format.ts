/**
 * The numbers of the panel.
 *
 * The panel shows the money of the store the way the store has always shown
 * it: with two decimals and the separator of the language of the application.
 */

/**
 * The language of the application, in the form that `Intl` understands.
 *
 * Laravel writes the locale with an underscore ("es_SV"), and the standard
 * of the language tags uses a dash ("es-SV"). Without the change, the
 * formatting of the numbers throws.
 */
export function intlLocale(locale: string): string {
    const normalized = locale.replace(/_/g, '-')

    try {
        Intl.NumberFormat.supportedLocalesOf([normalized])

        return normalized
    } catch {
        return 'en'
    }
}

/**
 * A number of money, with the decimals the store uses.
 */
export function money(value: number | string | null | undefined, locale: string): string {
    const amount = Number(value ?? 0)

    if (!Number.isFinite(amount)) {
        return money(0, locale)
    }

    return new Intl.NumberFormat(intlLocale(locale), {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(amount)
}

/**
 * A number of money with the currency of the store.
 */
export function moneyWithCurrency(
    value: number | string | null | undefined,
    currency: string,
    locale: string,
): string {
    return `${money(value, locale)} ${currency}`
}

/**
 * A range of money, when the product has more than one price.
 */
export function moneyRange(
    min: number | null | undefined,
    max: number | string | null | undefined,
    locale: string,
): string {
    if (min === null || min === undefined) {
        return '—'
    }

    if (max === null || max === undefined || Number(min) === Number(max)) {
        return money(min, locale)
    }

    return `${money(min, locale)} – ${money(max, locale)}`
}
