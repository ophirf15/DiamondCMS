export type UploadedMedia = {
    id: number
    path: string
    url: string
    original_name: string
    mime_type: string
    size: number
    alt_text?: string | null
}

export async function uploadMediaFile(csrf: string, file: File, altText?: string): Promise<UploadedMedia> {
    const form = new FormData()
    form.append('file', file)
    if (altText) form.append('alt_text', altText)

    const response = await fetch('/admin/api/media', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrf, Accept: 'application/json' },
        body: form,
    })

    if (!response.ok) {
        throw new Error(await response.text())
    }

    const row = await response.json() as UploadedMedia & { url?: string }
    return {
        ...row,
        url: row.url || `/storage/${row.path}`,
    }
}

export function isImageFile(file: File): boolean {
    return file.type.startsWith('image/')
}
