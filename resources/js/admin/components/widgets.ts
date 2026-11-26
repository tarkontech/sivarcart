/**
 * The registry of the widgets of the dashboard.
 *
 * A widget of the dashboard is a component of the bundle of the panel. The
 * server sends the name of the component and the data it needs, and the
 * dashboard looks the component up here.
 *
 * The registry is filled with the widgets of the package, and a merchant that
 * builds its own bundle fills it with its own.
 */
export const widgets: Record<string, unknown> = {}
