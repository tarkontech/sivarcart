/**
 * The shapes the panel receives from the server.
 *
 * These types mirror the props the package builds in PHP. The panel never
 * receives a model as it is: it receives the data the screen shows.
 */

/** The tone a state of the store is shown with. */
export type Tone = 'neutral' | 'success' | 'warning' | 'danger'

/** A state of the store, with its label and its tone. */
export interface State {
    value: string
    label: string
    tone: Tone
}

/** One page of the navigation of the panel. */
export interface NavItem {
    key: string
    label: string
    icon: string | null
    url: string
    group: string | null
    active: boolean
}

/** The store the panel belongs to. */
export interface StoreInfo {
    name: string | null
    logo: string | null
}

/** The member of the staff that is signed in. */
export interface UserInfo {
    name: string | null
    email: string | null
    roles: string[]
}

/** The numbers of a list of the panel. */
export interface PaginationMeta {
    current_page: number
    last_page: number
    per_page: number
    total: number
    from: number | null
    to: number | null
}

/** A page of a list of the panel. */
export interface Paginated<T> {
    data: T[]
    meta: PaginationMeta
}

/** One order of the list of the orders. */
export interface OrderListItem {
    uuid: string
    number: string
    customer: string
    date: string | null
    total: number
    currency: string
    status: State
    payment: State
}

/** One item of an order. */
export interface OrderItem {
    name: string
    quantity: number
    unit_price: number
    line_total: number
}

/** The totals of an order. */
export interface OrderTotals {
    subtotal: number
    discount: number
    tax: number
    shipping: number
    total: number
}

/** The buyer of an order. */
export interface OrderBuyer {
    name: string
    email: string
    phone: string
}

/** One entry of the history of an order. */
export interface OrderHistoryEntry {
    date: string | null
    text: string | null
}

/** One order of the detail of an order. */
export interface OrderDetail {
    uuid: string
    number: string
    date: string | null
    currency: string
    status: State
    payment: State
    items: OrderItem[]
    totals: OrderTotals
    buyer: OrderBuyer
    timeline: OrderHistoryEntry[]
}

/** One product of the catalog of the panel. */
export interface ProductListItem {
    uuid: string
    name: string
    sku: string | null
    price_min: number | null
    price_max: number | null
    stock: number
    status: State
}

/** One buyer of the panel. */
export interface CustomerListItem {
    email: string
    name: string
    phone: string
    orders: number
}

/** One role of the staff of the store. */
export interface RoleItem {
    name: string
    slug: string
    description: string
    permissions: string[]
    staff: number
}

/** The settings of the store. */
export interface StoreSettings {
    name: string
    legal_name: string
    tax_id: string
    email: string
    phone: string
}

/** One widget of the dashboard. */
export interface Widget {
    key: string
    component: string
    props: Record<string, unknown>
}

/** The texts of the panel, in the language of the application. */
export interface PanelText {
    title: string
    dashboard: {
        title: string
        description: string
        orders_today: string
        orders_awaiting: string
        orders_unpaid: string
        products: string
        low_stock: string
    }
    orders: {
        title: string
        search: string
        all_statuses: string
        empty: string
        back: string
        number: string
        customer: string
        date: string
        total: string
        status: string
        payment: string
        items: string
        item: string
        quantity: string
        price: string
        line_total: string
        subtotal: string
        discount: string
        tax: string
        shipping: string
        buyer: string
        email: string
        phone: string
        actions: string
        timeline: string
    }
    products: {
        title: string
        search: string
        empty: string
        name: string
        sku: string
        price: string
        stock: string
        status: string
    }
    customers: {
        title: string
        search: string
        empty: string
        name: string
        email: string
        phone: string
        orders: string
    }
    settings: {
        title: string
        description: string
        name: string
        legal_name: string
        tax_id: string
        email: string
        phone: string
        save: string
    }
    roles: {
        title: string
        description: string
        name: string
        slug: string
        permissions: string
        staff: string
    }
    login: {
        title: string
        email: string
        password: string
        remember: string
        submit: string
    }
    setup: {
        title: string
        body: string
        trait: string
        roles: string
    }
    pagination: {
        label: string
        previous: string
        next: string
    }
    theme: {
        light: string
        dark: string
    }
}

/** The props the panel shares with every page. */
export interface PanelSharedProps {
    [key: string]: unknown
    nav: NavItem[]
    store: StoreInfo
    user: UserInfo
    permissions: string[]
    current: string | null
    locale: string
    text: PanelText
}
