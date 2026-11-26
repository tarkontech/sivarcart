/**
 * The query of the address of the panel.
 *
 * The filters of a list travel in the address, so the list can be shared and
 * reloaded, and so a partial reload asks the server only for what changed.
 */
export function currentQuery(): Record<string, string> {
    const query: Record<string, string> = {}

    new URLSearchParams(window.location.search).forEach((value, key) => {
        query[key] = value
    })

    return query
}
