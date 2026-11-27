/**
 * The shapes the store receives from the server.
 *
 * These types mirror the props the package builds in PHP. The store never
 * receives a model as it is: it receives the data the screen shows.
 */

/** One category of the menu of the store. */
export interface CategoryItem {
    uuid: string
    name: string
    slug: string
    parent_id: number | null
    position: number
    is_featured: boolean
    image: string | null
}

/** One brand of the catalog. */
export interface BrandItem {
    id: number
    name: string
    slug: string
    logo: string | null
}

/** The store the shopper is buying in. */
export interface StoreInfo {
    name: string | null
    logo: string | null
    favicon: string | null
    currency: string
    locale: string
    timezone: string
    prices_include_tax: boolean
    email: string | null
    phone: string | null
    whatsapp: string | null
    address: string | null
}

/** The cart of the shopper, in the header. */
export interface CartSummary {
    count: number
    subtotal: number
    total: number
}

/** The buyer that is signed in. */
export interface BuyerInfo {
    name: string | null
    email: string | null
}

/** The numbers of a list of the store. */
export interface PaginationMeta {
    current_page: number
    last_page: number
    per_page: number
    total: number
    from: number | null
    to: number | null
}

/** A page of a list of the store. */
export interface Paginated<T> {
    data: T[]
    meta: PaginationMeta
}

/** One product of the grid of the store. */
export interface ProductCard {
    uuid: string
    name: string
    slug: string
    short_description: string
    image: string | null
    price_min: number | null
    price_max: number | null
    in_stock: boolean
    is_featured: boolean
    brand: string | null
}

/** One variant of a product, as the shopper picks it. */
export interface VariantItem {
    uuid: string
    name: string
    sku: string | null
    price: number
    compare_at_price: number | null
    available_stock: number
    is_default: boolean
    image: string | null
    options: { id: number; value: string; label: string }[]
}

/** One image of the gallery of a product. */
export interface ProductImageItem {
    url: string
    thumbnail_url: string
    alt: string
    position: number
    is_primary: boolean
}

/** The product of the page of la ficha. */
export interface ProductDetail {
    uuid: string
    name: string
    slug: string
    short_description: string
    description: string
    images: ProductImageItem[]
    variants: VariantItem[]
    brand: { name: string; slug: string } | null
    categories: { name: string; slug: string }[]
    price_min: number | null
    price_max: number | null
    in_stock: boolean
    is_featured: boolean
}

/** One line of the cart of the shopper. */
export interface CartLine {
    id: number
    name: string
    sku: string | null
    options: Record<string, unknown>
    unit_price: number
    quantity: number
    line_subtotal: number
    discount_total: number
    tax_total: number
    line_total: number
}

/** The cart of the shopper. */
export interface CartData {
    uuid: string
    currency: string
    item_count: number
    subtotal: number
    discount_total: number
    tax_total: number
    shipping_total: number
    total: number
    coupon: string | null
    items: CartLine[]
}

/** The totals of un carrito or de una orden. */
export interface Totals {
    subtotal: number
    discount: number
    tax: number
    shipping: number
    total: number
}

/** One item of an order. */
export interface OrderItem {
    name: string
    sku: string | null
    options: Record<string, unknown>
    unit_price: number
    quantity: number
    line_total: number
}

/** The order of the shopper. */
export interface OrderItemData {
    uuid: string
    number: string
    status: string
    status_label: string
    payment_status: string
    payment_status_label: string
    currency: string
    totals: Totals
    items: OrderItem[]
    buyer: { name: string; email: string; phone: string }
    shipping_address: Record<string, unknown> | null
    shipping_method: string | null
    placed_at: string | null
    payment: { status: string; method_code: string | null; reference: string | null } | null
}

/** The buyer of la tienda, in the page of the account. */
export interface BuyerProfile {
    name: string
    first_name: string
    last_name: string
    email: string
    phone: string | null
    document_type: string | null
    document_number: string | null
}

/** The address of a buyer. */
export interface AddressItem {
    label: string | null
    first_name: string | null
    last_name: string | null
    phone: string | null
    address_line1: string | null
    address_line2: string | null
    department: string | null
    municipality: string | null
    city: string | null
    reference: string | null
    postal_code: string | null
}

/** One method of shipping the shopper picks from. */
export interface ShippingMethodItem {
    code: string
    name: string
    type: string
    type_label: string
    price: number
    estimated_days: number | null
    instructions: string
}

/** One method of payment the shopper picks from. */
export interface PaymentMethodItem {
    code: string
    name: string
    type: string
    type_label: string
    instructions: string
    icon: string | null
}

/** One department of the country. */
export interface DepartmentItem {
    code: string
    name: string
}

/** The filters the catalog answers with. */
export interface CatalogFilters {
    category_id: number | null
    brand_id: number | null
    min_price: number | null
    max_price: number | null
    in_stock: boolean
    search: string | null
    sort: string
}

/** The texts of the store, in the language of the application. */
export interface StorefrontText {
    title: string
    nav: {
        home: string
        products: string
        cart: string
        account: string
        orders: string
        login: string
        register: string
        logout: string
    }
    home: {
        title: string
        subtitle: string
        featured: string
        featured_empty: string
        categories: string
        view_all: string
    }
    catalog: {
        title: string
        search: string
        search_placeholder: string
        all_categories: string
        all_brands: string
        min_price: string
        max_price: string
        in_stock: string
        sort: string
        filter: string
        clear: string
        empty: string
        previous: string
        next: string
        results: string
    }
    product: {
        add_to_cart: string
        add: string
        out_of_stock: string
        units: string
        only_left: string
        description: string
        sku: string
        brand: string
        categories: string
        quantity: string
        choose_variant: string
        variant: string
        back: string
    }
    cart: {
        title: string
        empty: string
        empty_hint: string
        line: string
        quantity: string
        price: string
        total: string
        remove: string
        subtotal: string
        discount: string
        tax: string
        shipping: string
        grand_total: string
        coupon: string
        coupon_placeholder: string
        apply: string
        remove_coupon: string
        clear: string
        checkout: string
        continue: string
        unit: string
    }
    checkout: {
        title: string
        summary: string
        buyer: string
        name: string
        email: string
        phone: string
        address: string
        address_line1: string
        address_line2: string
        department: string
        municipality: string
        city: string
        reference: string
        postal_code: string
        shipping_method: string
        shipping: string
        payment_method: string
        note: string
        note_placeholder: string
        coupon: string
        place_order: string
        estimated_days: string
        shipping_quote: string
    }
    orders: {
        title: string
        empty: string
        number: string
        date: string
        total: string
        status: string
        payment: string
        items: string
        view: string
        back: string
        pay_now: string
        paying: string
        payment_link: string
        payment_error: string
        thanks: string
        thanks_body: string
    }
    account: {
        title: string
        profile: string
        addresses: string
        orders: string
        no_addresses: string
        first_name: string
        last_name: string
        email: string
        phone: string
        document_type: string
        document_number: string
        document_none: string
        marketing: string
        save: string
        change_password: string
        current_password: string
        new_password: string
        confirm_password: string
        password_changed: string
    }
    auth: {
        login_title: string
        login_body: string
        register_title: string
        register_body: string
        email: string
        password: string
        password_confirmation: string
        submit_login: string
        submit_register: string
        forgot: string
        forgot_title: string
        forgot_body: string
        send_link: string
        reset_title: string
        reset_body: string
        new_password: string
        submit_reset: string
        no_account: string
        has_account: string
        accept_marketing: string
    }
    common: {
        currency: string
        errors: string
        saved: string
        required: string
        back: string
    }
}

/** The props the store shares with every page. */
export interface StorefrontSharedProps {
    [key: string]: unknown
    store: StoreInfo
    cart: CartSummary
    buyer: BuyerInfo
    categories: CategoryItem[]
    brands: BrandItem[]
    current: string | null
    locale: string
    prefix: string
    text: StorefrontText
}
