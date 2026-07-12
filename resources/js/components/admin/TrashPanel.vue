<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { RotateCcw, Trash2 } from '@lucide/vue'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { showActionToast } from '@/lib/actionToast'

type TrashPage = { id: number, title: string, slug: string, status: string, deleted_at: string }
type TrashMedia = { id: number, original_name: string, path: string, url?: string, mime_type: string, size: number, deleted_at: string }
type TrashProject = { id: number, title: string, slug: string, status: string, deleted_at: string }

type TrashPayload = {
    pages: TrashPage[]
    media: TrashMedia[]
    projects: TrashProject[]
}

const props = defineProps<{
    api: <T>(url: string, options?: RequestInit) => Promise<T>
}>()

const emit = defineEmits<{
    changed: []
}>()

const loading = ref(true)
const busyId = ref<string | null>(null)
const trash = ref<TrashPayload>({ pages: [], media: [], projects: [] })

async function load(): Promise<void> {
    loading.value = true
    try {
        trash.value = await props.api<TrashPayload>('/trash')
    } finally {
        loading.value = false
    }
}

async function restore(type: 'pages' | 'media' | 'projects', id: number, event?: Event): Promise<void> {
    busyId.value = `${type}-${id}`
    try {
        const path = type === 'pages'
            ? `/pages/${id}/restore`
            : type === 'media'
                ? `/media/${id}/restore`
                : `/portfolio/projects/${id}/restore`
        await props.api(path, { method: 'POST', body: '{}' })
        showActionToast(event, 'Restored')
        await load()
        emit('changed')
    } catch (error) {
        showActionToast(event, error instanceof Error ? error.message : 'Could not restore', 'error')
    } finally {
        busyId.value = null
    }
}

async function purge(type: 'pages' | 'media' | 'projects', id: number, label: string, event?: Event): Promise<void> {
    if (!confirm(`Permanently delete “${label}”? This cannot be undone.`)) return
    busyId.value = `${type}-${id}-purge`
    try {
        const path = type === 'pages'
            ? `/pages/${id}/force`
            : type === 'media'
                ? `/media/${id}/force`
                : `/portfolio/projects/${id}/force`
        await props.api(path, { method: 'DELETE' })
        showActionToast(event, 'Permanently deleted')
        await load()
        emit('changed')
    } catch (error) {
        showActionToast(event, error instanceof Error ? error.message : 'Could not delete', 'error')
    } finally {
        busyId.value = null
    }
}

function mediaUrl(item: TrashMedia): string {
    return item.url || `/storage/${item.path}`
}

onMounted(load)
</script>

<template>
    <section class="space-y-6">
        <div>
            <h1 class="text-3xl font-semibold tracking-tight">Trash</h1>
            <p class="text-muted-foreground text-sm">Restore deleted pages, media, and projects — or permanently remove them.</p>
        </div>

        <p v-if="loading" class="text-muted-foreground text-sm">Loading trash…</p>

        <template v-else>
            <Card>
                <CardHeader>
                    <CardTitle>Pages</CardTitle>
                    <CardDescription>{{ trash.pages.length }} in trash</CardDescription>
                </CardHeader>
                <CardContent class="space-y-2">
                    <p v-if="!trash.pages.length" class="text-muted-foreground text-sm">No deleted pages.</p>
                    <div
                        v-for="page in trash.pages"
                        :key="page.id"
                        class="flex flex-wrap items-center justify-between gap-3 rounded-lg border px-3 py-2"
                    >
                        <div class="min-w-0">
                            <p class="truncate font-medium">{{ page.title }}</p>
                            <p class="text-muted-foreground text-xs">/{{ page.slug }} · deleted {{ page.deleted_at }}</p>
                        </div>
                        <div class="flex gap-1.5">
                            <Button size="sm" variant="outline" class="gap-1.5" :disabled="busyId === `pages-${page.id}`" @click="restore('pages', page.id, $event)">
                                <RotateCcw class="size-3.5" />
                                Restore
                            </Button>
                            <Button size="sm" variant="ghost" class="text-destructive hover:text-destructive gap-1.5" :disabled="busyId === `pages-${page.id}-purge`" @click="purge('pages', page.id, page.title, $event)">
                                <Trash2 class="size-3.5" />
                                Delete forever
                            </Button>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Media</CardTitle>
                    <CardDescription>{{ trash.media.length }} in trash</CardDescription>
                </CardHeader>
                <CardContent class="space-y-2">
                    <p v-if="!trash.media.length" class="text-muted-foreground text-sm">No deleted media.</p>
                    <div
                        v-for="item in trash.media"
                        :key="item.id"
                        class="flex flex-wrap items-center justify-between gap-3 rounded-lg border px-3 py-2"
                    >
                        <div class="flex min-w-0 items-center gap-3">
                            <img
                                v-if="item.mime_type?.startsWith('image/')"
                                :src="mediaUrl(item)"
                                alt=""
                                class="size-10 rounded object-cover"
                            >
                            <div class="min-w-0">
                                <p class="truncate font-medium">{{ item.original_name }}</p>
                                <p class="text-muted-foreground text-xs">{{ Math.round(item.size / 1024) }} KB · deleted {{ item.deleted_at }}</p>
                            </div>
                        </div>
                        <div class="flex gap-1.5">
                            <Button size="sm" variant="outline" class="gap-1.5" :disabled="busyId === `media-${item.id}`" @click="restore('media', item.id, $event)">
                                <RotateCcw class="size-3.5" />
                                Restore
                            </Button>
                            <Button size="sm" variant="ghost" class="text-destructive hover:text-destructive gap-1.5" :disabled="busyId === `media-${item.id}-purge`" @click="purge('media', item.id, item.original_name, $event)">
                                <Trash2 class="size-3.5" />
                                Delete forever
                            </Button>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Projects</CardTitle>
                    <CardDescription>{{ trash.projects.length }} in trash</CardDescription>
                </CardHeader>
                <CardContent class="space-y-2">
                    <p v-if="!trash.projects.length" class="text-muted-foreground text-sm">No deleted projects.</p>
                    <div
                        v-for="project in trash.projects"
                        :key="project.id"
                        class="flex flex-wrap items-center justify-between gap-3 rounded-lg border px-3 py-2"
                    >
                        <div class="min-w-0">
                            <p class="truncate font-medium">{{ project.title }}</p>
                            <p class="text-muted-foreground text-xs">
                                /{{ project.slug }}
                                <Badge variant="secondary" class="ml-2">{{ project.status }}</Badge>
                            </p>
                        </div>
                        <div class="flex gap-1.5">
                            <Button size="sm" variant="outline" class="gap-1.5" :disabled="busyId === `projects-${project.id}`" @click="restore('projects', project.id, $event)">
                                <RotateCcw class="size-3.5" />
                                Restore
                            </Button>
                            <Button size="sm" variant="ghost" class="text-destructive hover:text-destructive gap-1.5" :disabled="busyId === `projects-${project.id}-purge`" @click="purge('projects', project.id, project.title, $event)">
                                <Trash2 class="size-3.5" />
                                Delete forever
                            </Button>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </template>
    </section>
</template>
