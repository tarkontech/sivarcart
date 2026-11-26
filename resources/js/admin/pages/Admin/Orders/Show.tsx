import { Head, Link, router, usePage } from '@inertiajs/react'
import { useState } from 'react'

import { Field } from '@/components/Form'
import { PageHeader } from '@/components/PageHeader'
import { StatusBadge } from '@/components/StatusBadge'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Textarea } from '@/components/ui/textarea'
import { PanelLayout } from '@/layouts/PanelLayout'
import { money, moneyWithCurrency } from '@/lib/format'
import type { OrderDetail, PanelSharedProps, State } from '@/types'

/**
 * One order of the store.
 *
 * The staff of the store reads the order and moves it through its lifecycle.
 * The moves are the ones of the service of the orders, so the panel never
 * writes the state of an order by itself.
 */
export default function OrderShow({
    order,
    canUpdate,
    transitions,
    refundable,
    canRefund,
    canReturn,
}: {
    order: OrderDetail
    canUpdate: boolean
    transitions: State[]
    refundable: number
    canRefund: boolean
    canReturn: boolean
}) {
    const { nav, text, locale } = usePage<PanelSharedProps>().props
    const [pending, setPending] = useState<string | null>(null)
    const [amount, setAmount] = useState('')
    const [reason, setReason] = useState('')
    const [errors, setErrors] = useState<Record<string, string>>({})

    const back = nav.find((item) => item.key === 'orders')?.url

    const move = (status: string) => {
        setPending(status)

        router.post(
            `${window.location.pathname}/status`,
            { status },
            {
                preserveScroll: true,
                onFinish: () => setPending(null),
            },
        )
    }

    /*
    | A refund gives the money back and a return says that the units came
    | back to the store, so the two are two different writes.
    */
    const refund = () => {
        setPending('refund')
        setErrors({})

        router.post(
            `${window.location.pathname}/refund`,
            { amount, reason },
            {
                preserveScroll: true,
                onError: (received) => setErrors(received as Record<string, string>),
                onFinish: () => setPending(null),
            },
        )
    }

    const markReturned = () => {
        setPending('return')
        setErrors({})

        router.post(
            `${window.location.pathname}/return`,
            { reason },
            {
                preserveScroll: true,
                onError: (received) => setErrors(received as Record<string, string>),
                onFinish: () => setPending(null),
            },
        )
    }

    return (
        <PanelLayout>
            <Head title={order.number} />

            <div className="flex flex-col gap-4">
                {back ? (
                    <Link
                        href={back}
                        className="w-fit text-sm text-muted-foreground underline-offset-4 hover:underline"
                    >
                        {text.orders.back}
                    </Link>
                ) : null}

                <PageHeader
                    title={order.number}
                    actions={
                        <>
                            <StatusBadge state={order.status} />
                            <StatusBadge state={order.payment} />
                        </>
                    }
                />
            </div>

            <div className="grid gap-6 lg:grid-cols-2">
                <Card>
                    <CardHeader>
                        <CardTitle>{text.orders.items}</CardTitle>
                    </CardHeader>

                    <CardContent className="flex flex-col gap-4">
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="border-b">
                                    <th className="py-2 text-left font-medium text-muted-foreground">
                                        {text.orders.item}
                                    </th>
                                    <th className="py-2 text-left font-medium text-muted-foreground">
                                        {text.orders.quantity}
                                    </th>
                                    <th className="py-2 text-left font-medium text-muted-foreground">
                                        {text.orders.price}
                                    </th>
                                    <th className="py-2 text-left font-medium text-muted-foreground">
                                        {text.orders.line_total}
                                    </th>
                                </tr>
                            </thead>

                            <tbody>
                                {order.items.map((item, index) => (
                                    <tr key={`${item.name}-${index}`} className="border-b last:border-0">
                                        <td className="py-2 pr-2">{item.name}</td>
                                        <td className="py-2 pr-2">{item.quantity}</td>
                                        <td className="py-2 pr-2">{money(item.unit_price, locale)}</td>
                                        <td className="py-2">{money(item.line_total, locale)}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>

                        <dl className="flex flex-col gap-2 text-sm">
                            <div className="flex justify-between">
                                <dt className="text-muted-foreground">{text.orders.subtotal}</dt>
                                <dd>{money(order.totals.subtotal, locale)}</dd>
                            </div>
                            <div className="flex justify-between">
                                <dt className="text-muted-foreground">{text.orders.discount}</dt>
                                <dd>{money(order.totals.discount, locale)}</dd>
                            </div>
                            <div className="flex justify-between">
                                <dt className="text-muted-foreground">{text.orders.tax}</dt>
                                <dd>{money(order.totals.tax, locale)}</dd>
                            </div>
                            <div className="flex justify-between">
                                <dt className="text-muted-foreground">{text.orders.shipping}</dt>
                                <dd>{money(order.totals.shipping, locale)}</dd>
                            </div>
                            <div className="flex justify-between border-t pt-2 font-medium">
                                <dt>{text.orders.total}</dt>
                                <dd>{moneyWithCurrency(order.totals.total, order.currency, locale)}</dd>
                            </div>
                        </dl>
                    </CardContent>
                </Card>

                <div className="flex flex-col gap-6">
                    <Card>
                        <CardHeader>
                            <CardTitle>{text.orders.buyer}</CardTitle>
                        </CardHeader>

                        <CardContent>
                            <dl className="flex flex-col gap-2 text-sm">
                                <div className="flex justify-between gap-4">
                                    <dt className="text-muted-foreground">{text.orders.customer}</dt>
                                    <dd className="text-right">{order.buyer.name}</dd>
                                </div>
                                <div className="flex justify-between gap-4">
                                    <dt className="text-muted-foreground">{text.orders.email}</dt>
                                    <dd className="text-right">{order.buyer.email}</dd>
                                </div>
                                <div className="flex justify-between gap-4">
                                    <dt className="text-muted-foreground">{text.orders.phone}</dt>
                                    <dd className="text-right">{order.buyer.phone}</dd>
                                </div>
                                <div className="flex justify-between gap-4">
                                    <dt className="text-muted-foreground">{text.orders.date}</dt>
                                    <dd className="text-right">{order.date ?? '—'}</dd>
                                </div>
                            </dl>
                        </CardContent>
                    </Card>

                    {canUpdate && transitions.length > 0 ? (
                        <Card>
                            <CardHeader>
                                <CardTitle>{text.orders.actions}</CardTitle>
                            </CardHeader>

                            <CardContent className="flex flex-wrap gap-2">
                                {transitions.map((transition) => (
                                    <Button
                                        key={transition.value}
                                        type="button"
                                        variant={
                                            transition.value === 'cancelled'
                                                ? 'destructive'
                                                : 'default'
                                        }
                                        disabled={pending !== null}
                                        onClick={() => move(transition.value)}
                                    >
                                        {transition.label}
                                    </Button>
                                ))}
                            </CardContent>
                        </Card>
                    ) : null}

                    {canRefund || canReturn ? (
                        <Card>
                            <CardHeader>
                                <CardTitle>{text.orders.refund}</CardTitle>
                            </CardHeader>

                            <CardContent className="flex flex-col gap-4">
                                {canRefund ? (
                                    <Field
                                        label={text.orders.refund_amount}
                                        htmlFor="refund-amount"
                                        error={errors.amount}
                                        hint={text.orders.refund_max.replace(
                                            ':amount',
                                            money(refundable, locale),
                                        )}
                                    >
                                        <Input
                                            id="refund-amount"
                                            type="number"
                                            step="0.01"
                                            min="0.01"
                                            max={refundable}
                                            value={amount}
                                            onChange={(event) => setAmount(event.target.value)}
                                        />
                                    </Field>
                                ) : null}

                                <Field
                                    label={text.orders.refund_reason}
                                    htmlFor="refund-reason"
                                    error={errors.reason}
                                >
                                    <Textarea
                                        id="refund-reason"
                                        value={reason}
                                        onChange={(event) => setReason(event.target.value)}
                                    />
                                </Field>

                                <div className="flex flex-wrap gap-2">
                                    {canRefund ? (
                                        <Button
                                            type="button"
                                            variant="destructive"
                                            disabled={pending !== null}
                                            onClick={() => refund()}
                                        >
                                            {text.orders.refund_submit}
                                        </Button>
                                    ) : null}

                                    {canReturn ? (
                                        <Button
                                            type="button"
                                            variant="outline"
                                            disabled={pending !== null}
                                            onClick={() => markReturned()}
                                        >
                                            {text.orders.return_submit}
                                        </Button>
                                    ) : null}
                                </div>
                            </CardContent>
                        </Card>
                    ) : null}

                    {order.timeline.length > 0 ? (
                        <Card>
                            <CardHeader>
                                <CardTitle>{text.orders.timeline}</CardTitle>
                            </CardHeader>

                            <CardContent>
                                <ul className="flex flex-col gap-2 text-sm">
                                    {order.timeline.map((entry, index) => (
                                        <li
                                            key={`${entry.date}-${index}`}
                                            className="flex justify-between gap-4"
                                        >
                                            <span className="text-muted-foreground">
                                                {entry.date ?? '—'}
                                            </span>
                                            <span className="text-right">{entry.text ?? '—'}</span>
                                        </li>
                                    ))}
                                </ul>
                            </CardContent>
                        </Card>
                    ) : null}
                </div>
            </div>
        </PanelLayout>
    )
}
