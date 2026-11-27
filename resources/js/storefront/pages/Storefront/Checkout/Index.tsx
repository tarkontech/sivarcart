import { Head, router, usePage } from '@inertiajs/react'
import { useState } from 'react'
import type { FormEvent } from 'react'

import { CartTotals } from '@storefront/components/CartTotals'
import { EmptyState } from '@storefront/components/EmptyState'
import { Button } from '@storefront/components/ui/button'
import { Input } from '@storefront/components/ui/input'
import { Label } from '@storefront/components/ui/label'
import { Separator } from '@storefront/components/ui/separator'
import { Textarea } from '@storefront/components/ui/textarea'
import { moneyWithCurrency } from '@storefront/lib/format'
import { storePath } from '@storefront/lib/url'
import { StorefrontLayout } from '@storefront/layouts/StorefrontLayout'
import type {
    AddressItem,
    BuyerProfile,
    CartData,
    DepartmentItem,
    PaymentMethodItem,
    ShippingMethodItem,
    StorefrontSharedProps,
} from '@storefront/types'

/**
 * The checkout of the shopper.
 *
 * The form sends lo que el servidor valida, con los mismos nombres que usa
 * la API de la tienda: las reglas de las dos son las mismas.
 */
export default function CheckoutIndex({
    cart,
    payment_methods,
    shipping_methods,
    departments,
    buyer,
    address,
}: {
    cart: CartData
    payment_methods: PaymentMethodItem[]
    shipping_methods: ShippingMethodItem[]
    departments: DepartmentItem[]
    buyer: BuyerProfile
    address: AddressItem | null
}) {
    const { store, prefix, locale, text } = usePage<StorefrontSharedProps>().props

    const errors = usePage<StorefrontSharedProps>().props.errors as
        | Record<string, string>
        | undefined

    const [quote, setQuote] = useState<number | null>(null)
    const [quoting, setQuoting] = useState(false)

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault()

        const form = new FormData(event.currentTarget)

        router.post(storePath(prefix, 'checkout'), form, { preserveScroll: true })
    }

    const askForTheQuote = async (form: HTMLFormElement) => {
        const data = new FormData(form)

        const method = String(data.get('shipping_method_code') ?? '')

        if (method === '') {
            return
        }

        setQuoting(true)

        try {
            const response = await fetch(storePath(prefix, 'checkout/envio'), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrfToken(),
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({
                    shipping_method_code: method,
                    address: {
                        address_line1: data.get('shipping_address[address_line1]'),
                        municipality: data.get('shipping_address[municipality]'),
                        department: data.get('shipping_address[department]'),
                    },
                }),
            })

            if (!response.ok) {
                return
            }

            const payload = (await response.json()) as { amount: number }

            setQuote(Number(payload.amount))
        } finally {
            setQuoting(false)
        }
    }

    if (cart.items.length === 0) {
        return (
            <StorefrontLayout>
                <Head title={text.checkout.title} />

                <EmptyState>{text.cart.empty}</EmptyState>
            </StorefrontLayout>
        )
    }

    return (
        <StorefrontLayout>
            <Head title={text.checkout.title} />

            <form
                onSubmit={submit}
                className="grid gap-8 lg:grid-cols-[1fr_20rem]"
            >
                <div className="flex flex-col gap-6">
                    <h1 className="text-2xl font-semibold tracking-tight">
                        {text.checkout.title}
                    </h1>

                    <section className="flex flex-col gap-4 rounded-lg border border-border bg-card p-4">
                        <h2 className="text-lg font-semibold">{text.checkout.buyer}</h2>

                        <div className="grid gap-4 sm:grid-cols-2">
                            <div className="flex flex-col gap-2">
                                <Label htmlFor="customer_name">{text.checkout.name}</Label>

                                <Input
                                    id="customer_name"
                                    name="customer_name"
                                    defaultValue={buyer.name}
                                    required
                                />

                                {errors?.customer_name ? (
                                    <p className="text-sm text-destructive">
                                        {errors.customer_name}
                                    </p>
                                ) : null}
                            </div>

                            <div className="flex flex-col gap-2">
                                <Label htmlFor="customer_email">{text.checkout.email}</Label>

                                <Input
                                    id="customer_email"
                                    name="customer_email"
                                    type="email"
                                    defaultValue={buyer.email}
                                />

                                {errors?.customer_email ? (
                                    <p className="text-sm text-destructive">
                                        {errors.customer_email}
                                    </p>
                                ) : null}
                            </div>

                            <div className="flex flex-col gap-2">
                                <Label htmlFor="customer_phone">{text.checkout.phone}</Label>

                                <Input
                                    id="customer_phone"
                                    name="customer_phone"
                                    defaultValue={buyer.phone ?? ''}
                                />

                                {errors?.customer_phone ? (
                                    <p className="text-sm text-destructive">
                                        {errors.customer_phone}
                                    </p>
                                ) : null}
                            </div>
                        </div>
                    </section>

                    <section className="flex flex-col gap-4 rounded-lg border border-border bg-card p-4">
                        <h2 className="text-lg font-semibold">{text.checkout.address}</h2>

                        <div className="grid gap-4 sm:grid-cols-2">
                            <div className="flex flex-col gap-2">
                                <Label htmlFor="first_name">{text.account.first_name}</Label>

                                <Input
                                    id="first_name"
                                    name="shipping_address[first_name]"
                                    defaultValue={address?.first_name ?? buyer.first_name}
                                />
                            </div>

                            <div className="flex flex-col gap-2">
                                <Label htmlFor="last_name">{text.account.last_name}</Label>

                                <Input
                                    id="last_name"
                                    name="shipping_address[last_name]"
                                    defaultValue={address?.last_name ?? buyer.last_name}
                                />
                            </div>

                            <div className="flex flex-col gap-2 sm:col-span-2">
                                <Label htmlFor="address_line1">
                                    {text.checkout.address_line1}
                                </Label>

                                <Input
                                    id="address_line1"
                                    name="shipping_address[address_line1]"
                                    defaultValue={address?.address_line1 ?? ''}
                                    required
                                />

                                {errors?.['shipping_address.address_line1'] ? (
                                    <p className="text-sm text-destructive">
                                        {errors['shipping_address.address_line1']}
                                    </p>
                                ) : null}
                            </div>

                            <div className="flex flex-col gap-2 sm:col-span-2">
                                <Label htmlFor="address_line2">
                                    {text.checkout.address_line2}
                                </Label>

                                <Input
                                    id="address_line2"
                                    name="shipping_address[address_line2]"
                                    defaultValue={address?.address_line2 ?? ''}
                                />
                            </div>

                            <div className="flex flex-col gap-2">
                                <Label htmlFor="department">{text.checkout.department}</Label>

                                <select
                                    id="department"
                                    name="shipping_address[department]"
                                    defaultValue={address?.department ?? ''}
                                    className="h-9 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                                >
                                    <option value="">{text.checkout.department}</option>

                                    {departments.map((department) => (
                                        <option key={department.code} value={department.code}>
                                            {department.name}
                                        </option>
                                    ))}
                                </select>
                            </div>

                            <div className="flex flex-col gap-2">
                                <Label htmlFor="municipality">
                                    {text.checkout.municipality}
                                </Label>

                                <Input
                                    id="municipality"
                                    name="shipping_address[municipality]"
                                    defaultValue={address?.municipality ?? ''}
                                    required
                                />

                                {errors?.['shipping_address.municipality'] ? (
                                    <p className="text-sm text-destructive">
                                        {errors['shipping_address.municipality']}
                                    </p>
                                ) : null}
                            </div>

                            <div className="flex flex-col gap-2">
                                <Label htmlFor="city">{text.checkout.city}</Label>

                                <Input
                                    id="city"
                                    name="shipping_address[city]"
                                    defaultValue={address?.city ?? ''}
                                />
                            </div>

                            <div className="flex flex-col gap-2">
                                <Label htmlFor="reference">{text.checkout.reference}</Label>

                                <Input
                                    id="reference"
                                    name="shipping_address[reference]"
                                    defaultValue={address?.reference ?? ''}
                                />
                            </div>

                            <div className="flex flex-col gap-2">
                                <Label htmlFor="postal_code">
                                    {text.checkout.postal_code}
                                </Label>

                                <Input
                                    id="postal_code"
                                    name="shipping_address[postal_code]"
                                    defaultValue={address?.postal_code ?? ''}
                                />
                            </div>
                        </div>
                    </section>

                    <section className="flex flex-col gap-4 rounded-lg border border-border bg-card p-4">
                        <h2 className="text-lg font-semibold">
                            {text.checkout.shipping_method}
                        </h2>

                        <div className="grid gap-4 sm:grid-cols-2">
                            <div className="flex flex-col gap-2">
                                <Label htmlFor="shipping_method_code">
                                    {text.checkout.shipping_method}
                                </Label>

                                <select
                                    id="shipping_method_code"
                                    name="shipping_method_code"
                                    className="h-9 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                                >
                                    <option value="">
                                        {text.checkout.shipping_method}
                                    </option>

                                    {shipping_methods.map((method) => (
                                        <option key={method.code} value={method.code}>
                                            {method.name} —{' '}
                                            {moneyWithCurrency(
                                                method.price,
                                                store.currency,
                                                locale,
                                            )}
                                        </option>
                                    ))}
                                </select>

                                {errors?.shipping_method_code ? (
                                    <p className="text-sm text-destructive">
                                        {errors.shipping_method_code}
                                    </p>
                                ) : null}
                            </div>

                            <div className="flex items-end">
                                <Button
                                    type="button"
                                    variant="outline"
                                    disabled={quoting}
                                    onClick={(event) => {
                                        const form = event.currentTarget.form

                                        if (form) {
                                            void askForTheQuote(form)
                                        }
                                    }}
                                >
                                    {text.checkout.shipping_quote}
                                </Button>
                            </div>
                        </div>

                        {quote !== null ? (
                            <p className="text-sm text-muted-foreground">
                                {text.checkout.shipping}:{' '}
                                <span className="font-medium text-foreground">
                                    {moneyWithCurrency(quote, store.currency, locale)}
                                </span>
                            </p>
                        ) : null}
                    </section>

                    <section className="flex flex-col gap-4 rounded-lg border border-border bg-card p-4">
                        <h2 className="text-lg font-semibold">
                            {text.checkout.payment_method}
                        </h2>

                        <div className="flex flex-col gap-2">
                            {payment_methods.map((method) => (
                                <label
                                    key={method.code}
                                    className="flex cursor-pointer items-start gap-3 rounded-md border border-border p-3"
                                >
                                    <input
                                        type="radio"
                                        name="payment_method_code"
                                        value={method.code}
                                        defaultChecked={
                                            payment_methods[0]?.code === method.code
                                        }
                                        className="mt-1"
                                    />

                                    <span className="flex flex-col gap-1">
                                        <span className="font-medium">{method.name}</span>

                                        {method.instructions ? (
                                            <span className="text-sm text-muted-foreground">
                                                {method.instructions}
                                            </span>
                                        ) : null}
                                    </span>
                                </label>
                            ))}

                            {errors?.payment_method_code ? (
                                <p className="text-sm text-destructive">
                                    {errors.payment_method_code}
                                </p>
                            ) : null}
                        </div>
                    </section>

                    <section className="flex flex-col gap-4 rounded-lg border border-border bg-card p-4">
                        <h2 className="text-lg font-semibold">{text.cart.coupon}</h2>

                        <Input name="coupon_code" defaultValue={cart.coupon ?? ''} />

                        <div className="flex flex-col gap-2">
                            <Label htmlFor="customer_note">{text.checkout.note}</Label>

                            <Textarea
                                id="customer_note"
                                name="customer_note"
                                rows={3}
                                placeholder={text.checkout.note_placeholder}
                            />
                        </div>
                    </section>
                </div>

                <aside className="flex h-fit flex-col gap-4 rounded-lg border border-border bg-card p-4">
                    <h2 className="text-lg font-semibold">{text.checkout.summary}</h2>

                    <div className="flex flex-col gap-2 text-sm">
                        {cart.items.map((line) => (
                            <div key={line.id} className="flex justify-between gap-4">
                                <span className="text-muted-foreground">
                                    {line.quantity} × {line.name}
                                </span>

                                <span>
                                    {moneyWithCurrency(
                                        line.line_total,
                                        store.currency,
                                        locale,
                                    )}
                                </span>
                            </div>
                        ))}
                    </div>

                    <Separator />

                    <CartTotals
                        totals={{
                            subtotal: cart.subtotal,
                            discount: cart.discount_total,
                            tax: cart.tax_total,
                            shipping: quote ?? cart.shipping_total,
                            total: cart.total + (quote ?? 0),
                        }}
                        currency={store.currency}
                        locale={locale}
                        labels={{
                            subtotal: text.cart.subtotal,
                            discount: text.cart.discount,
                            tax: text.cart.tax,
                            shipping: text.cart.shipping,
                            grand_total: text.cart.grand_total,
                        }}
                    />

                    <Separator />

                    <Button type="submit">{text.checkout.place_order}</Button>
                </aside>
            </form>
        </StorefrontLayout>
    )
}

/**
 * The token of the CSRF of the page.
 */
function csrfToken(): string {
    return (
        document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? ''
    )
}
