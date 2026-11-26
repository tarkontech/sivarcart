import { Head, useForm, usePage } from '@inertiajs/react'
import type { FormEvent } from 'react'

import { Field, FormActions } from '@/components/Form'
import { LinkButton } from '@/components/LinkButton'
import { PageHeader } from '@/components/PageHeader'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Checkbox } from '@/components/ui/checkbox'
import { Input } from '@/components/ui/input'
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select'
import { Textarea } from '@/components/ui/textarea'
import { PanelLayout } from '@/layouts/PanelLayout'
import type {
    BrandItem,
    CategoryItem,
    PanelSharedProps,
    ProductForm,
    State,
    VariantForm,
} from '@/types'

/**
 * The form of a product of the catalog.
 *
 * The product and its variants are written together: the price and the stock
 * of a product live in its variants, so the screen writes both at once.
 */
export default function ProductsForm({
    product,
    brands,
    categories,
    statuses,
    types,
    canUpdate,
}: {
    product: ProductForm
    brands: BrandItem[]
    categories: CategoryItem[]
    statuses: State[]
    types: State[]
    canUpdate: boolean
}) {
    const { nav, text } = usePage<PanelSharedProps>().props

    const base = nav.find((item) => item.key === 'products')?.url ?? window.location.pathname

    const { data, setData, post, put, processing, errors } = useForm({
        name: product.name,
        slug: product.slug,
        short_description: product.short_description,
        description: product.description,
        status: product.status,
        type: product.type,
        brand_id: product.brand_id === null ? '' : String(product.brand_id),
        weight: product.weight === null ? '' : String(product.weight),
        is_featured: product.is_featured,
        is_virtual: product.is_virtual,
        categories: product.categories.map((id) => String(id)),
        variants: product.variants,
    })

    const isSimple = data.type === 'simple'

    const visibleVariants = isSimple ? data.variants.slice(0, 1) : data.variants

    const submit = (event: FormEvent) => {
        event.preventDefault()

        if (product.uuid === null) {
            post(base, { preserveScroll: true })

            return
        }

        put(`${base}/${product.uuid}`, { preserveScroll: true })
    }

    const updateVariant = (index: number, field: keyof VariantForm, value: unknown) => {
        setData(
            'variants',
            data.variants.map((variant, position) =>
                position === index ? { ...variant, [field]: value } : variant,
            ),
        )
    }

    const addVariant = () => {
        setData('variants', [
            ...data.variants,
            {
                uuid: null,
                name: '',
                sku: '',
                barcode: '',
                price: '0',
                compare_at_price: '',
                cost_price: '',
                stock_quantity: 0,
                low_stock_threshold: null,
                is_default: false,
                is_active: true,
            },
        ])
    }

    const removeVariant = (index: number) => {
        setData(
            'variants',
            data.variants.filter((_, position) => position !== index),
        )
    }

    const toggleCategory = (id: number, checked: boolean) => {
        setData(
            'categories',
            checked
                ? [...data.categories, String(id)]
                : data.categories.filter((value) => value !== String(id)),
        )
    }

    const toggleDefault = (index: number) => {
        setData(
            'variants',
            data.variants.map((variant, position) => ({
                ...variant,
                is_default: position === index ? !variant.is_default : false,
            })),
        )
    }

    return (
        <PanelLayout>
            <Head title={text.products.edit} />

            <div className="flex flex-col gap-4">
                <LinkButton href={base} variant="link" size="sm" className="w-fit p-0">
                    {text.products.back}
                </LinkButton>

                <PageHeader title={product.name || text.products.new} />
            </div>

            {!canUpdate ? (
                <Card>
                    <CardContent>
                        <p className="text-sm text-muted-foreground">
                            {text.products.read_only}
                        </p>
                    </CardContent>
                </Card>
            ) : null}

            <form onSubmit={submit} className="flex flex-col gap-6">
                <Card className="max-w-3xl">
                    <CardHeader>
                        <CardTitle>{text.products.name}</CardTitle>
                    </CardHeader>

                    <CardContent className="flex flex-col gap-4">
                        <Field label={text.products.name} htmlFor="name" error={errors.name}>
                            <Input
                                id="name"
                                name="name"
                                value={data.name}
                                disabled={!canUpdate}
                                onChange={(event) => setData('name', event.target.value)}
                            />
                        </Field>

                        <Field label={text.products.slug} htmlFor="slug" error={errors.slug}>
                            <Input
                                id="slug"
                                name="slug"
                                value={data.slug}
                                disabled={!canUpdate}
                                onChange={(event) => setData('slug', event.target.value)}
                            />
                        </Field>

                        <Field
                            label={text.products.short_description}
                            htmlFor="short_description"
                            error={errors.short_description}
                        >
                            <Input
                                id="short_description"
                                name="short_description"
                                value={data.short_description}
                                disabled={!canUpdate}
                                onChange={(event) =>
                                    setData('short_description', event.target.value)
                                }
                            />
                        </Field>

                        <Field
                            label={text.products.description}
                            htmlFor="description"
                            error={errors.description}
                        >
                            <Textarea
                                id="description"
                                name="description"
                                value={data.description}
                                disabled={!canUpdate}
                                onChange={(event) => setData('description', event.target.value)}
                            />
                        </Field>

                        <div className="grid gap-4 sm:grid-cols-2">
                            <Field label={text.products.status} htmlFor="status" error={errors.status}>
                                <Select
                                    value={data.status}
                                    disabled={!canUpdate}
                                    onValueChange={(value) =>
                                        setData('status', value ?? data.status)
                                    }
                                >
                                    <SelectTrigger id="status">
                                        <SelectValue />
                                    </SelectTrigger>

                                    <SelectContent>
                                        {statuses.map((status) => (
                                            <SelectItem key={status.value} value={status.value}>
                                                {status.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </Field>

                            <Field label={text.products.type} htmlFor="type" error={errors.type}>
                                <Select
                                    value={data.type}
                                    disabled={!canUpdate}
                                    onValueChange={(value) => setData('type', value ?? data.type)}
                                >
                                    <SelectTrigger id="type">
                                        <SelectValue />
                                    </SelectTrigger>

                                    <SelectContent>
                                        {types.map((type) => (
                                            <SelectItem key={type.value} value={type.value}>
                                                {type.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </Field>
                        </div>

                        <div className="grid gap-4 sm:grid-cols-2">
                            <Field label={text.products.brand} htmlFor="brand_id" error={errors.brand_id}>
                                <Select
                                    value={data.brand_id === '' ? 'none' : data.brand_id}
                                    disabled={!canUpdate}
                                    onValueChange={(value) =>
                                        setData('brand_id', !value || value === 'none' ? '' : value)
                                    }
                                >
                                    <SelectTrigger id="brand_id">
                                        <SelectValue placeholder={text.products.no_brand} />
                                    </SelectTrigger>

                                    <SelectContent>
                                        <SelectItem value="none">
                                            {text.products.no_brand}
                                        </SelectItem>

                                        {brands.map((brand) => (
                                            <SelectItem key={brand.id} value={String(brand.id)}>
                                                {brand.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </Field>

                            <Field label={text.products.weight} htmlFor="weight" error={errors.weight}>
                                <Input
                                    id="weight"
                                    name="weight"
                                    type="number"
                                    step="0.001"
                                    min="0"
                                    value={data.weight}
                                    disabled={!canUpdate}
                                    onChange={(event) => setData('weight', event.target.value)}
                                />
                            </Field>
                        </div>

                        <div className="flex flex-wrap gap-6">
                            <label className="flex items-center gap-2 text-sm">
                                <Checkbox
                                    checked={data.is_featured}
                                    disabled={!canUpdate}
                                    onCheckedChange={(checked) =>
                                        setData('is_featured', checked === true)
                                    }
                                />
                                {text.products.featured}
                            </label>

                            <label className="flex items-center gap-2 text-sm">
                                <Checkbox
                                    checked={data.is_virtual}
                                    disabled={!canUpdate}
                                    onCheckedChange={(checked) =>
                                        setData('is_virtual', checked === true)
                                    }
                                />
                                {text.products.virtual}
                            </label>
                        </div>

                        {categories.length > 0 ? (
                            <Field label={text.products.categories} error={errors.categories}>
                                <div className="flex flex-col gap-2">
                                    {categories.map((category) => (
                                        <label
                                            key={category.id}
                                            className="flex items-center gap-2 text-sm"
                                        >
                                            <Checkbox
                                                checked={data.categories.includes(
                                                    String(category.id),
                                                )}
                                                disabled={!canUpdate}
                                                onCheckedChange={(checked) =>
                                                    toggleCategory(category.id, checked === true)
                                                }
                                            />
                                            {category.name}
                                        </label>
                                    ))}
                                </div>
                            </Field>
                        ) : null}
                    </CardContent>
                </Card>

                <Card className="max-w-3xl">
                    <CardHeader>
                        <CardTitle>{text.products.variants}</CardTitle>
                    </CardHeader>

                    <CardContent className="flex flex-col gap-6">
                        {visibleVariants.map((variant, index) => (
                            <div
                                key={variant.uuid ?? `new-${index}`}
                                className="flex flex-col gap-4 rounded-lg border border-border p-4"
                            >
                                <div className="grid gap-4 sm:grid-cols-2">
                                    <Field
                                        label={text.products.variant}
                                        htmlFor={`variant-name-${index}`}
                                        error={errors[`variants.${index}.name`]}
                                    >
                                        <Input
                                            id={`variant-name-${index}`}
                                            value={variant.name}
                                            disabled={!canUpdate}
                                            onChange={(event) =>
                                                updateVariant(index, 'name', event.target.value)
                                            }
                                        />
                                    </Field>

                                    <Field
                                        label={text.products.sku}
                                        htmlFor={`variant-sku-${index}`}
                                        error={errors[`variants.${index}.sku`]}
                                    >
                                        <Input
                                            id={`variant-sku-${index}`}
                                            value={variant.sku}
                                            disabled={!canUpdate}
                                            onChange={(event) =>
                                                updateVariant(index, 'sku', event.target.value)
                                            }
                                        />
                                    </Field>
                                </div>

                                <div className="grid gap-4 sm:grid-cols-3">
                                    <Field
                                        label={text.products.price}
                                        htmlFor={`variant-price-${index}`}
                                        error={errors[`variants.${index}.price`]}
                                    >
                                        <Input
                                            id={`variant-price-${index}`}
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            value={variant.price}
                                            disabled={!canUpdate}
                                            onChange={(event) =>
                                                updateVariant(index, 'price', event.target.value)
                                            }
                                        />
                                    </Field>

                                    <Field
                                        label={text.products.compare_at_price}
                                        htmlFor={`variant-compare-${index}`}
                                        error={errors[`variants.${index}.compare_at_price`]}
                                    >
                                        <Input
                                            id={`variant-compare-${index}`}
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            value={variant.compare_at_price}
                                            disabled={!canUpdate}
                                            onChange={(event) =>
                                                updateVariant(
                                                    index,
                                                    'compare_at_price',
                                                    event.target.value,
                                                )
                                            }
                                        />
                                    </Field>

                                    <Field
                                        label={text.products.cost_price}
                                        htmlFor={`variant-cost-${index}`}
                                        error={errors[`variants.${index}.cost_price`]}
                                    >
                                        <Input
                                            id={`variant-cost-${index}`}
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            value={variant.cost_price}
                                            disabled={!canUpdate}
                                            onChange={(event) =>
                                                updateVariant(index, 'cost_price', event.target.value)
                                            }
                                        />
                                    </Field>
                                </div>

                                <div className="grid gap-4 sm:grid-cols-3">
                                    <Field
                                        label={text.products.stock}
                                        htmlFor={`variant-stock-${index}`}
                                        error={errors[`variants.${index}.stock_quantity`]}
                                    >
                                        <Input
                                            id={`variant-stock-${index}`}
                                            type="number"
                                            min="0"
                                            value={variant.stock_quantity}
                                            disabled={!canUpdate}
                                            onChange={(event) =>
                                                updateVariant(
                                                    index,
                                                    'stock_quantity',
                                                    Number(event.target.value),
                                                )
                                            }
                                        />
                                    </Field>

                                    <Field
                                        label={text.products.low_stock_threshold}
                                        htmlFor={`variant-threshold-${index}`}
                                        error={errors[`variants.${index}.low_stock_threshold`]}
                                    >
                                        <Input
                                            id={`variant-threshold-${index}`}
                                            type="number"
                                            min="0"
                                            value={variant.low_stock_threshold ?? ''}
                                            disabled={!canUpdate}
                                            onChange={(event) =>
                                                updateVariant(
                                                    index,
                                                    'low_stock_threshold',
                                                    event.target.value === ''
                                                        ? null
                                                        : Number(event.target.value),
                                                )
                                            }
                                        />
                                    </Field>

                                    <Field
                                        label={text.products.barcode}
                                        htmlFor={`variant-barcode-${index}`}
                                        error={errors[`variants.${index}.barcode`]}
                                    >
                                        <Input
                                            id={`variant-barcode-${index}`}
                                            value={variant.barcode}
                                            disabled={!canUpdate}
                                            onChange={(event) =>
                                                updateVariant(index, 'barcode', event.target.value)
                                            }
                                        />
                                    </Field>
                                </div>

                                <div className="flex flex-wrap items-center gap-6">
                                    <label className="flex items-center gap-2 text-sm">
                                        <input
                                            type="radio"
                                            name="default_variant"
                                            className="size-4 accent-primary"
                                            checked={variant.is_default}
                                            disabled={!canUpdate}
                                            onChange={() => toggleDefault(index)}
                                        />
                                        {text.products.default_variant}
                                    </label>

                                    <label className="flex items-center gap-2 text-sm">
                                        <Checkbox
                                            checked={variant.is_active}
                                            disabled={!canUpdate}
                                            onCheckedChange={(checked) =>
                                                updateVariant(index, 'is_active', checked === true)
                                            }
                                        />
                                        {text.products.active}
                                    </label>

                                    {!isSimple && canUpdate ? (
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="sm"
                                            className="text-destructive"
                                            onClick={() => removeVariant(index)}
                                        >
                                            {text.products.remove_variant}
                                        </Button>
                                    ) : null}
                                </div>
                            </div>
                        ))}

                        {!isSimple && canUpdate ? (
                            <div>
                                <Button type="button" variant="outline" size="sm" onClick={addVariant}>
                                    {text.products.add_variant}
                                </Button>
                            </div>
                        ) : null}
                    </CardContent>
                </Card>

                {canUpdate ? (
                    <FormActions submit={text.products.save} processing={processing}>
                        <LinkButton href={base} variant="outline">
                            {text.products.cancel}
                        </LinkButton>
                    </FormActions>
                ) : null}
            </form>
        </PanelLayout>
    )
}
