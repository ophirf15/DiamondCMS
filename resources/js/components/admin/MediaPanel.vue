<script setup lang="ts">
import { computed, ref } from 'vue'
import { Trash2, Upload } from '@lucide/vue'
import { Button } from '@/components/ui/button'
import { Card, CardContent } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { toast } from 'vue-sonner'

export type MediaItem = {
    id: number
    path: string
    url?: string
    original_name: string
    mime_type: string
    size: number
    alt_text?: string | null
}

const props = defineProps<{
    items: MediaItem[]
    csrf: string
    api: <T>(url: string, options?: RequestInit) => Promise<T>
    selectable?: boolean
}>()

const emit = defineEmits<{
    refreshed: [items: MediaItem[]]
    select: [item: MediaItem]
}>()

const uploading = ref(false)
const filter = ref('')
const fileInput = ref<HTMLInputElement | null>(null)

const filtered = computed(() => {
    const q = filter.value.trim().toLowerCase()
    if (!q) return props.items
    return props.items.filter((item) => item.original_name.toLowerCase().includes(q) || (item.alt_text ?? '').toLowerCase().includes(q))
})

function mediaUrl(item: MediaItem): string {
    const raw = item.url || `/storage/${item.path}`
    if (raw.startsWith('http://') || raw.startsWith('https://')) {
        try {
            return new URL(raw).pathname
        } catch {
            return raw
        }
    }
    return raw.startsWith('/') ? raw : `/${raw}`
}

function onThumbError(event: Event): void {
    const img = event.target as HTMLImageElement
    img.style.display = 'none'
    const fallback = img.parentElement?.querySelector('[data-thumb-fallback]')
    if (fallback instanceof HTMLElement) fallback.hidden = false
}

function isImage(item: MediaItem): boolean {
    return item.mime_type.startsWith('image/')
}

async function refresh(): Promise<void> {
    const result = await props.api<{ data: MediaItem[] }>('/media')
    emit('refreshed', result.data)
}

async function upload(event: Event): Promise<void> {
    const input = event.target as HTMLInputElement
    const files = input.files
    if (!files?.length) return
    uploading.value = true
    try {
        for (const file of Array.from(files)) {
            const form = new FormData()
            form.append('file', file)
            const response = await fetch('/admin/api/media', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': props.csrf, Accept: 'application/json' },
                body: form,
            })
            if (!response.ok) throw new Error(await response.text())
        }
        await refresh()
        toast.success(files.length === 1 ? 'Uploaded' : `${files.length} files uploaded`)
    } catch (error) {
        toast.error(error instanceof Error ? error.message : 'Upload failed')
    } finally {
        uploading.value = false
        input.value = ''
    }
}

async function remove(item: MediaItem): Promise<void> {
    try {
        await props.api(`/media/${item.id}`, { method: 'DELETE' })
        await refresh()
        toast.success('Moved to trash')
    } catch (error) {
        toast.error(error instanceof Error ? error.message : 'Could not delete')
    }
}

async function copyUrl(item: MediaItem): Promise<void> {
    await navigator.clipboard.writeText(mediaUrl(item))
    toast.success('URL copied')
}

function onSelect(item: MediaItem): void {
    if (props.selectable) emit('select', item)
}
</script>

<template>
    <section class="space-y-4">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <h1 v-if="!selectable" class="text-3xl font-semibold tracking-tight">Media</h1>
                <p class="text-muted-foreground text-sm">{{ selectable ? 'Pick an image for this block.' : 'Upload images and files you can place on pages.' }}</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <Input v-model="filter" class="w-48" placeholder="Search library…" />
                <input ref="fileInput" type="file" accept="image/*,.pdf" multiple class="sr-only" @change="upload">
                <Button class="gap-2" :disabled="uploading" @click="fileInput?.click()">
                    <Upload class="size-4" />
                    <span>{{ uploading ? 'Uploading…' : 'Upload' }}</span>
                </Button>
            </div>
        </div>

        <div v-if="filtered.length === 0" class="rounded-xl border border-dashed p-10 text-center">
            <p class="text-muted-foreground text-sm">No media yet. Upload an image to get started.</p>
        </div>

        <div v-else class="grid gap-3 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-4">
            <Card
                v-for="item in filtered"
                :key="item.id"
                class="overflow-hidden"
                :class="selectable ? 'hover:ring-primary/40 cursor-pointer hover:ring-2' : ''"
                @click="onSelect(item)"
            >
                <div class="bg-muted relative flex aspect-square items-center justify-center overflow-hidden">
                    <img
                        v-if="isImage(item)"
                        :src="mediaUrl(item)"
                        :alt="item.alt_text || item.original_name"
                        class="h-full w-full object-cover"
                        @error="onThumbError"
                    >
                    <span
                        data-thumb-fallback
                        class="text-muted-foreground absolute inset-0 flex items-center justify-center px-3 text-center text-xs"
                        :hidden="isImage(item)"
                    >{{ item.original_name }}</span>
                    <span v-if="!isImage(item)" class="text-muted-foreground px-3 text-center text-xs">{{ item.original_name }}</span>
                </div>
                <CardContent class="space-y-2 p-3">
                    <p class="truncate text-sm font-medium" :title="item.original_name">{{ item.original_name }}</p>
                    <p class="text-muted-foreground text-xs">{{ Math.round(item.size / 1024) }} KB</p>
                    <div v-if="!selectable" class="flex gap-2">
                        <Button size="sm" variant="outline" class="flex-1" @click.stop="copyUrl(item)">Copy URL</Button>
                        <Button size="sm" variant="destructive" @click.stop="remove(item)">
                            <Trash2 class="size-3.5" />
                        </Button>
                    </div>
                </CardContent>
            </Card>
        </div>
    </section>
</template>
