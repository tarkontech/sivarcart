/**
 * The addresses of the store.
 *
 * The store is mounted under a prefix that the merchant can change, así que
 * el bundle no escribe las direcciones de la tienda en el código: las arma
 * con el prefijo que le llega del servidor.
 */

/**
 * The address of a path of the store.
 *
 * The prefix may come with a slash on either side, so the address is always
 * the same: the prefix, a slash and the path.
 */
export function storePath(prefix: string, path: string): string {
    const base = prefix.replace(/^\/+|\/+$/g, '')
    const tail = path.replace(/^\/+|\/+$/g, '')

    if (base === '') {
        return tail === '' ? '/' : `/${tail}`
    }

    return tail === '' ? `/${base}` : `/${base}/${tail}`
}
