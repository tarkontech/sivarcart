import { useForm, usePage } from '@inertiajs/react'
import { useEffect, useRef, useState } from 'react'

import { Field } from '@/components/Form'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import type { PanelSharedProps } from '@/types'

/**
 * The upload of one of the images of the store.
 *
 * The file travels as a file and not as the body of a form, so the screen
 * sends it the way the browser knows how to send it. The name of the field
 * is the name the server expects, so the logo and the favicon do not need a
 * screen of their own.
 */
export function ImageUpload({
    name,
    label,
    hint,
    url,
}: {
    name: string
    label: string
    hint: string
    url: string | null
}) {
    const { text } = usePage<PanelSharedProps>().props

    const input = useRef<HTMLInputElement>(null)

    const { data, setData, post, processing, errors, clearErrors } = useForm<
        Record<string, File | null>
    >({ [name]: null })

    const [preview, setPreview] = useState<string | null>(url)

    useEffect(() => {
        setPreview(url)
    }, [url])

    const submit = (event: React.FormEvent) => {
        event.preventDefault()

        if (data[name] === null) {
            return
        }

        post(`${window.location.pathname}/${name}`, {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                setData(name, null)

                if (input.current !== null) {
                    input.current.value = ''
                }
            },
        })
    }

    return (
        <form onSubmit={submit} className="flex flex-col gap-2">
            <Field label={label} htmlFor={`upload-${name}`} error={errors[name]} hint={hint}>
                <div className="flex flex-wrap items-center gap-3">
                    {preview ? (
                        <img
                            src={preview}
                            alt={label}
                            className="size-12 rounded-md border border-border object-contain"
                        />
                    ) : null}

                    <Input
                        ref={input}
                        id={`upload-${name}`}
                        name={name}
                        type="file"
                        accept="image/*"
                        className="max-w-64"
                        onChange={(event) => {
                            const file = event.target.files?.[0] ?? null

                            setData(name, file)
                            clearErrors()

                            if (file !== null) {
                                setPreview(URL.createObjectURL(file))
                            }
                        }}
                    />
                </div>
            </Field>

            <div>
                <Button type="submit" size="sm" disabled={processing || data[name] === null}>
                    {text.settings.save}
                </Button>
            </div>
        </form>
    )
}
