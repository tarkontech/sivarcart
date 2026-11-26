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

/** One variant of a product, as the form of the panel carries it. */
export interface VariantForm {
    uuid: string | null
    name: string
    sku: string
    barcode: string
    price: string
    compare_at_price: string
    cost_price: string
    stock_quantity: number
    low_stock_threshold: number | null
    is_default: boolean
    is_active: boolean
}

/** The product the form of the panel writes. */
export interface ProductForm {
    uuid: string | null
    name: string
    slug: string
    short_description: string
    description: string
    status: string
    type: string
    is_featured: boolean
    is_virtual: boolean
    brand_id: number | null
    weight: number | null
    categories: number[]
    variants: VariantForm[]
}

/** One category of the catalog of the panel. */
export interface CategoryItem {
    uuid: string
    id: number
    name: string
    slug: string
    description: string
    parent_id: number | null
    position: number
    is_active: boolean
    is_featured: boolean
    products: number
}

/** One brand of the catalog of the panel. */
export interface BrandItem {
    id: number
    name: string
    slug: string
    description: string
    position: number
    is_active: boolean
    products: number
    logo: string | null
}

/** One shipping method of the panel. */
export interface ShippingMethodItem {
    uuid: string
    code: string
    name: string
    type: string
    type_label: string
    price: number
    min_order_amount: number | null
    max_order_amount: number | null
    estimated_days: number | null
    instructions: string
    position: number
    is_active: boolean
}

/** One payment method of the panel. */
export interface PaymentMethodItem {
    uuid: string
    code: string
    name: string
    type: string
    type_label: string
    driver: string | null
    instructions: string
    icon: string | null
    position: number
    is_active: boolean
    has_credentials: boolean
}

/** One pair of credentials of a payment method. */
export interface CredentialPair {
    key: string
    value: string
}

/** One member of the staff of the store. */
export interface StaffMember {
    id: number
    name: string | null
    email: string | null
    roles: string[]
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
    granted: string[]
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
        refund: string
        refund_amount: string
        refund_reason: string
        refund_max: string
        refund_submit: string
        return: string
        return_reason: string
        return_submit: string
    }
    products: {
        title: string
        back: string
        search: string
        empty: string
        name: string
        sku: string
        price: string
        stock: string
        status: string
        actions: string
        new: string
        edit: string
        create: string
        save: string
        cancel: string
        read_only: string
        slug: string
        short_description: string
        description: string
        type: string
        brand: string
        no_brand: string
        categories: string
        featured: string
        virtual: string
        weight: string
        variants: string
        variant: string
        add_variant: string
        remove_variant: string
        default_variant: string
        active: string
        compare_at_price: string
        cost_price: string
        low_stock_threshold: string
        barcode: string
        not_found: string
        delete_confirm: string
    }
    categories: {
        title: string
        back: string
        search: string
        empty: string
        name: string
        slug: string
        parent: string
        no_parent: string
        description: string
        position: string
        products: string
        active: string
        featured: string
        actions: string
        new: string
        edit: string
        create: string
        save: string
        cancel: string
        not_found: string
        delete_confirm: string
    }
    brands: {
        title: string
        back: string
        search: string
        empty: string
        name: string
        slug: string
        description: string
        position: string
        products: string
        active: string
        actions: string
        new: string
        edit: string
        create: string
        save: string
        cancel: string
        not_found: string
        delete_confirm: string
    }
    shipping_methods: {
        title: string
        back: string
        search: string
        empty: string
        name: string
        code: string
        type: string
        price: string
        min_order_amount: string
        max_order_amount: string
        estimated_days: string
        instructions: string
        position: string
        active: string
        actions: string
        new: string
        edit: string
        create: string
        save: string
        cancel: string
        not_found: string
        delete_confirm: string
    }
    payment_methods: {
        title: string
        back: string
        search: string
        empty: string
        name: string
        code: string
        type: string
        driver: string
        credentials: string
        credentials_hint: string
        add_credential: string
        remove_credential: string
        instructions: string
        icon: string
        position: string
        active: string
        actions: string
        new: string
        edit: string
        create: string
        save: string
        cancel: string
        not_found: string
        delete_confirm: string
    }
    staff: {
        title: string
        description: string
        search: string
        empty: string
        name: string
        email: string
        roles: string
        actions: string
        new: string
        create: string
        password: string
        password_confirmation: string
        remove: string
        remove_confirm: string
        save_roles: string
        not_found: string
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
        logo: string
        favicon: string
        logo_hint: string
        favicon_hint: string
    }
    roles: {
        title: string
        description: string
        name: string
        slug: string
        permissions: string
        staff: string
        new: string
        create: string
        save: string
        cancel: string
        remove: string
        remove_confirm: string
        system: string
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
