import { Head, useForm, usePage } from '@inertiajs/react'
import type { FormEvent } from 'react'

import { Field, FormActions } from '@/components/Form'
import { LinkButton } from '@/components/LinkButton'
import { PageHeader } from '@/components/PageHeader'
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
import type { CategoryItem, PanelSharedProps } from '@/types'

/**
 * The form of a category of the catalog.
 *
 * A category is filed under another one, so the form offers the categories of
 * the catalog as the parents it can have.
 */
export default function CategoriesForm({
    category,
    categories,
    canUpdate,
}: {
    category: CategoryItem | null
    categories: CategoryItem[]
    canUpdate: boolean
}) {
    const { nav, text } = usePage<PanelSharedProps>().props

    const base = nav.find((item) => item.key === 'categories')?.url ?? window.location.pathname

    const { data, setData, post, put, processing, errors } = useForm({
        name: category?.name ?? '',
        slug: category?.slug ?? '',
        description: category?.description ?? '',
        parent_id: category?.parent_id === null || category === null ? 'none' : String(category.parent_id),
        position: String(category?.position ?? 0),
        is_active: category?.is_active ?? true,
        is_featured: category?.is_featured ?? false,
    })

    /*
    | A category cannot be filed under itself or under one of the categories
    | that are already under it, so the tree is walked and the ones that
    | would make a loop are not offered.
    */
    const excluded = (() => {
        const ids = new Set<string>()

        if (category === null) {
            return ids
        }

        ids.add(category.uuid)

        let frontier = [category.id]

        while (frontier.length > 0) {
            const children = categories.filter(
                (option) => option.parent_id !== null && frontier.includes(option.parent_id),
            )

            frontier = []

            for (const child of children) {
                ids.add(child.uuid)
                frontier.push(child.id)
            }
        }

        return ids
    })()

    const submit = (event: FormEvent) => {
        event.preventDefault()

        if (category === null) {
            post(base, { preserveScroll: true })

            return
        }

        put(`${base}/${category.uuid}`, { preserveScroll: true })
    }

    return (
        <PanelLayout>
            <Head title={text.categories.edit} />

            <div className="flex flex-col gap-4">
                <LinkButton href={base} variant="link" size="sm" className="w-fit p-0">
                    {text.categories.back}
                </LinkButton>

                <PageHeader title={category?.name || text.categories.new} />
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
                <Card className="max-w-2xl">
                    <CardHeader>
                        <CardTitle>{text.categories.name}</CardTitle>
                    </CardHeader>

                    <CardContent className="flex flex-col gap-4">
                        <Field label={text.categories.name} htmlFor="name" error={errors.name}>
                            <Input
                                id="name"
                                name="name"
                                value={data.name}
                                disabled={!canUpdate}
                                onChange={(event) => setData('name', event.target.value)}
                            />
                        </Field>

                        <Field label={text.categories.slug} htmlFor="slug" error={errors.slug}>
                            <Input
                                id="slug"
                                name="slug"
                                value={data.slug}
                                disabled={!canUpdate}
                                onChange={(event) => setData('slug', event.target.value)}
                            />
                        </Field>

                        <Field
                            label={text.categories.description}
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

                        <Field label={text.categories.parent} htmlFor="parent_id" error={errors.parent_id}>
                            <Select
                                value={data.parent_id}
                                disabled={!canUpdate}
                                onValueChange={(value) =>
                                    setData('parent_id', !value || value === 'none' ? 'none' : value)
                                }
                            >
                                <SelectTrigger id="parent_id">
                                    <SelectValue placeholder={text.categories.no_parent} />
                                </SelectTrigger>

                                <SelectContent>
                                    <SelectItem value="none">
                                        {text.categories.no_parent}
                                    </SelectItem>

                                    {categories
                                        .filter((option) => !excluded.has(option.uuid))
                                        .map((option) => (
                                            <SelectItem key={option.uuid} value={String(option.id)}>
                                                {option.name}
                                            </SelectItem>
                                        ))}
                                </SelectContent>
                            </Select>
                        </Field>

                        <Field label={text.categories.position} htmlFor="position" error={errors.position}>
                            <Input
                                id="position"
                                name="position"
                                type="number"
                                min="0"
                                value={data.position}
                                disabled={!canUpdate}
                                onChange={(event) => setData('position', event.target.value)}
                            />
                        </Field>

                        <div className="flex flex-wrap gap-6">
                            <label className="flex items-center gap-2 text-sm">
                                <Checkbox
                                    checked={data.is_active}
                                    disabled={!canUpdate}
                                    onCheckedChange={(checked) =>
                                        setData('is_active', checked === true)
                                    }
                                />
                                {text.categories.active}
                            </label>

                            <label className="flex items-center gap-2 text-sm">
                                <Checkbox
                                    checked={data.is_featured}
                                    disabled={!canUpdate}
                                    onCheckedChange={(checked) =>
                                        setData('is_featured', checked === true)
                                    }
                                />
                                {text.categories.featured}
                            </label>
                        </div>
                    </CardContent>
                </Card>

                {canUpdate ? (
                    <FormActions submit={text.categories.save} processing={processing}>
                        <LinkButton href={base} variant="outline">
                            {text.categories.cancel}
                        </LinkButton>
                    </FormActions>
                ) : null}
            </form>
        </PanelLayout>
    )
}
