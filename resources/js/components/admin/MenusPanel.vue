<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { Plus, Trash2 } from '@lucide/vue'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { toast } from 'vue-sonner'

type PageOption = { id: number, title: string, slug: string }
type MenuItem = {
    id?: number
    label: string
    url: string | null
    page_id: number | null
    children?: MenuItem[]
}
type MenuRow = {
    id: number
    name: string
    location: string
    items: MenuItem[]
}

const props = defineProps<{
    api: <T>(url: string, options?: RequestInit) => Promise<T>
    pages: PageOption[]
}>()

const location = ref<'header' | 'footer'>('header')
const menus = ref<MenuRow[]>([])
const items = ref<MenuItem[]>([])
const saving = ref(false)

function blankItem(): MenuItem {
    return { label: 'New link', url: '/', page_id: null, children: [] }
}

async function load(): Promise<void> {
    try {
        menus.value = await props.api<MenuRow[]>('/menus')
        syncLocation()
    } catch (error) {
        toast.error(error instanceof Error ? error.message : 'Could not load menus')
    }
}

function syncLocation(): void {
    const current = menus.value.find((menu) => menu.location === location.value)
    items.value = current?.items?.length
        ? JSON.parse(JSON.stringify(current.items)) as MenuItem[]
        : location.value === 'header'
            ? [{ label: 'Projects', url: '/projects', page_id: null, children: [] }]
            : []
}

function setLocation(next: 'header' | 'footer'): void {
    location.value = next
    syncLocation()
}

function addItem(): void {
    items.value.push(blankItem())
}

function removeItem(index: number): void {
    items.value.splice(index, 1)
}

function applyPage(item: MenuItem, pageId: string): void {
    if (!pageId) {
        item.page_id = null
        return
    }
    const page = props.pages.find((candidate) => candidate.id === Number(pageId))
    if (!page) return
    item.page_id = page.id
    item.label = item.label === 'New link' || !item.label ? page.title : item.label
    item.url = page.slug === 'home' ? '/' : `/${page.slug}`
}

async function save(): Promise<void> {
    saving.value = true
    try {
        const payload = {
            name: location.value === 'header' ? 'Header' : 'Footer',
            location: location.value,
            items: items.value.map((item) => ({
                label: item.label,
                url: item.page_id ? null : item.url,
                page_id: item.page_id,
                children: item.children ?? [],
            })),
        }
        const saved = await props.api<MenuRow>('/menus', { method: 'POST', body: JSON.stringify(payload) })
        const others = menus.value.filter((menu) => menu.location !== saved.location)
        menus.value = [...others, saved]
        items.value = JSON.parse(JSON.stringify(saved.items ?? [])) as MenuItem[]
        toast.success(`${location.value} menu saved`)
    } catch (error) {
        toast.error(error instanceof Error ? error.message : 'Could not save menu')
    } finally {
        saving.value = false
    }
}

onMounted(load)
</script>

<template>
    <section class="space-y-4">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <h1 class="text-3xl font-semibold tracking-tight">Menus</h1>
                <p class="text-muted-foreground text-sm">Control header and footer links. Admin appears only when you are logged in.</p>
            </div>
            <Button :disabled="saving" @click="save">Save menu</Button>
        </div>

        <div class="flex w-fit flex-wrap gap-1 rounded-lg border p-1">
            <Button size="sm" :variant="location === 'header' ? 'secondary' : 'ghost'" @click="setLocation('header')">Header</Button>
            <Button size="sm" :variant="location === 'footer' ? 'secondary' : 'ghost'" @click="setLocation('footer')">Footer</Button>
        </div>

        <Card>
            <CardHeader class="flex-row items-center justify-between space-y-0">
                <div>
                    <CardTitle class="capitalize">{{ location }} links</CardTitle>
                    <CardDescription>Order is top-to-bottom as listed.</CardDescription>
                </div>
                <Button variant="outline" size="sm" class="gap-1.5" @click="addItem">
                    <Plus class="size-3.5" />
                    <span>Add link</span>
                </Button>
            </CardHeader>
            <CardContent class="space-y-4">
                <div v-if="items.length === 0" class="text-muted-foreground rounded-lg border border-dashed p-6 text-center text-sm">
                    No links yet. Add pages or custom URLs.
                </div>
                <div
                    v-for="(item, index) in items"
                    :key="index"
                    class="grid gap-3 rounded-lg border p-3 md:grid-cols-[1fr_1fr_1fr_auto]"
                >
                    <div class="space-y-2">
                        <Label>Label</Label>
                        <Input v-model="item.label" />
                    </div>
                    <div class="space-y-2">
                        <Label>Page</Label>
                        <select
                            class="border-input bg-background flex h-9 w-full rounded-md border px-3 text-sm"
                            :value="item.page_id ?? ''"
                            @change="applyPage(item, ($event.target as HTMLSelectElement).value)"
                        >
                            <option value="">Custom URL</option>
                            <option v-for="page in pages" :key="page.id" :value="page.id">{{ page.title }}</option>
                        </select>
                    </div>
                    <div class="space-y-2">
                        <Label>URL</Label>
                        <Input v-model="item.url" :disabled="!!item.page_id" placeholder="/about" />
                    </div>
                    <div class="flex items-end">
                        <Button variant="destructive" size="icon" @click="removeItem(index)">
                            <Trash2 class="size-4" />
                        </Button>
                    </div>
                </div>
            </CardContent>
        </Card>
    </section>
</template>
