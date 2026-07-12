<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { ExternalLink, ImageIcon, Plus, Trash2, X } from '@lucide/vue'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table'
import MediaPanel, { type MediaItem } from '@/components/admin/MediaPanel.vue'
import IconPicker from '@/components/ui/IconPicker.vue'
import SocialBrandIcon from '@/components/builder/SocialBrandIcon.vue'
import { toast } from 'vue-sonner'

type Category = { id: number, name: string, slug: string }
type GalleryItem = { src: string, alt: string }
type LogoItem = { label: string, icon: string, image: string, url: string }

type Project = {
    id: number
    title: string
    slug: string
    status: string
    visibility: string
    is_featured: boolean | number
    summary?: string | null
    case_study?: string | null
    url?: string | null
    cover_image?: string | null
    category_id?: number | null
    skills?: string | string[]
    gallery?: GalleryItem[]
    logos?: LogoItem[]
}

type ProjectForm = {
    title: string
    summary: string
    case_study: string
    status: string
    visibility: string
    is_featured: boolean
    url: string
    cover_image: string
    skills: string
    category_id: number | null
    gallery: GalleryItem[]
    logos: LogoItem[]
}

const props = defineProps<{
    api: <T>(url: string, options?: RequestInit) => Promise<T>
    csrf: string
}>()

const emptyForm = (): ProjectForm => ({
    title: '',
    summary: '',
    case_study: '',
    status: 'published',
    visibility: 'public',
    is_featured: true,
    url: '',
    cover_image: '',
    skills: '',
    category_id: null,
    gallery: [],
    logos: [],
})

const projects = ref<Project[]>([])
const categories = ref<Category[]>([])
const editing = ref<(ProjectForm & { id: number }) | null>(null)
const newCategory = ref('')
const form = ref<ProjectForm>(emptyForm())
const mediaFiles = ref<MediaItem[]>([])
const showMediaPicker = ref(false)
const mediaTarget = ref<'create-cover' | 'edit-cover' | 'create-gallery' | 'edit-gallery' | 'create-logo' | 'edit-logo'>('create-cover')
const mediaIndex = ref(0)
const iconPickerOpen = ref(false)
const iconTarget = ref<'create' | 'edit'>('create')
const iconIndex = ref(0)

function skillsText(project: Project): string {
    if (Array.isArray(project.skills)) return project.skills.join(', ')
    if (typeof project.skills === 'string') {
        try {
            const parsed = JSON.parse(project.skills) as string[]
            return Array.isArray(parsed) ? parsed.join(', ') : project.skills
        } catch {
            return project.skills
        }
    }
    return ''
}

function asGallery(value: unknown): GalleryItem[] {
    if (!Array.isArray(value)) return []
    return value.map((row) => {
        if (typeof row === 'string') return { src: row, alt: '' }
        const item = (row && typeof row === 'object') ? row as Record<string, unknown> : {}
        return { src: String(item.src ?? item.url ?? ''), alt: String(item.alt ?? '') }
    }).filter((row) => row.src)
}

function asLogos(value: unknown): LogoItem[] {
    if (!Array.isArray(value)) return []
    return value.map((row) => {
        const item = (row && typeof row === 'object') ? row as Record<string, unknown> : {}
        return {
            label: String(item.label ?? ''),
            icon: String(item.icon ?? ''),
            image: String(item.image ?? ''),
            url: String(item.url ?? ''),
        }
    }).filter((row) => row.label || row.icon || row.image)
}

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

async function load(): Promise<void> {
    projects.value = await props.api<Project[]>('/portfolio/projects')
    categories.value = await props.api<Category[]>('/portfolio/categories')
}

async function loadMedia(): Promise<void> {
    const result = await props.api<{ data: MediaItem[] }>('/media')
    mediaFiles.value = result.data
}

async function openMedia(target: typeof mediaTarget.value, index = 0): Promise<void> {
    mediaTarget.value = target
    mediaIndex.value = index
    showMediaPicker.value = true
    if (!mediaFiles.value.length) await loadMedia()
}

function pickMedia(item: MediaItem): void {
    const url = mediaUrl(item)
    const target = mediaTarget.value
    if (target === 'create-cover') form.value.cover_image = url
    else if (target === 'edit-cover' && editing.value) editing.value.cover_image = url
    else if (target === 'create-gallery') {
        const next = [...form.value.gallery]
        if (next[mediaIndex.value]) next[mediaIndex.value] = { ...next[mediaIndex.value], src: url }
        else next.push({ src: url, alt: item.alt_text || item.original_name || '' })
        form.value.gallery = next
    } else if (target === 'edit-gallery' && editing.value) {
        const next = [...editing.value.gallery]
        if (next[mediaIndex.value]) next[mediaIndex.value] = { ...next[mediaIndex.value], src: url }
        else next.push({ src: url, alt: item.alt_text || item.original_name || '' })
        editing.value.gallery = next
    } else if (target === 'create-logo') {
        const next = [...form.value.logos]
        if (next[mediaIndex.value]) next[mediaIndex.value] = { ...next[mediaIndex.value], image: url, icon: '' }
        form.value.logos = next
    } else if (target === 'edit-logo' && editing.value) {
        const next = [...editing.value.logos]
        if (next[mediaIndex.value]) next[mediaIndex.value] = { ...next[mediaIndex.value], image: url, icon: '' }
        editing.value.logos = next
    }
    showMediaPicker.value = false
}

function openIconPicker(target: 'create' | 'edit', index: number): void {
    iconTarget.value = target
    iconIndex.value = index
    iconPickerOpen.value = true
}

function onIconPicked(slug: string): void {
    if (iconTarget.value === 'create') {
        const next = [...form.value.logos]
        if (next[iconIndex.value]) next[iconIndex.value] = { ...next[iconIndex.value], icon: slug, image: '' }
        form.value.logos = next
    } else if (editing.value) {
        const next = [...editing.value.logos]
        if (next[iconIndex.value]) next[iconIndex.value] = { ...next[iconIndex.value], icon: slug, image: '' }
        editing.value.logos = next
    }
}

async function createCategory(): Promise<void> {
    if (!newCategory.value.trim()) return
    await props.api('/portfolio/categories', {
        method: 'POST',
        body: JSON.stringify({ name: newCategory.value.trim() }),
    })
    newCategory.value = ''
    await load()
    toast.success('Category created')
}

function payloadFrom(formValue: ProjectForm) {
    return {
        title: formValue.title,
        summary: formValue.summary,
        case_study: formValue.case_study || null,
        status: formValue.status,
        visibility: formValue.visibility,
        is_featured: formValue.is_featured,
        url: formValue.url || null,
        cover_image: formValue.cover_image || null,
        category_id: formValue.category_id,
        skills: formValue.skills.split(',').map((s) => s.trim()).filter(Boolean),
        gallery: formValue.gallery.filter((row) => row.src.trim()),
        logos: formValue.logos.filter((row) => row.label.trim() || row.icon || row.image),
    }
}

async function create(): Promise<void> {
    if (!form.value.title.trim()) return
    try {
        await props.api('/portfolio/projects', {
            method: 'POST',
            body: JSON.stringify(payloadFrom(form.value)),
        })
        form.value = emptyForm()
        await load()
        toast.success('Project created')
    } catch (error) {
        toast.error(error instanceof Error ? error.message : 'Could not create project')
    }
}

function startEdit(project: Project): void {
    editing.value = {
        id: project.id,
        title: project.title,
        summary: project.summary || '',
        case_study: project.case_study || '',
        status: project.status,
        visibility: project.visibility,
        is_featured: !!project.is_featured,
        url: project.url || '',
        cover_image: project.cover_image || '',
        skills: skillsText(project),
        category_id: project.category_id ?? null,
        gallery: asGallery(project.gallery),
        logos: asLogos(project.logos),
    }
}

async function saveEdit(): Promise<void> {
    if (!editing.value) return
    try {
        await props.api(`/portfolio/projects/${editing.value.id}`, {
            method: 'PUT',
            body: JSON.stringify(payloadFrom(editing.value)),
        })
        editing.value = null
        await load()
        toast.success('Project updated')
    } catch (error) {
        toast.error(error instanceof Error ? error.message : 'Could not update project')
    }
}

async function toggleFeatured(project: Project): Promise<void> {
    await props.api(`/portfolio/projects/${project.id}`, {
        method: 'PUT',
        body: JSON.stringify({ is_featured: !project.is_featured }),
    })
    await load()
}

async function setStatus(project: Project, status: string): Promise<void> {
    await props.api(`/portfolio/projects/${project.id}`, {
        method: 'PUT',
        body: JSON.stringify({ status, visibility: status === 'published' ? 'public' : project.visibility }),
    })
    await load()
}

async function remove(project: Project): Promise<void> {
    if (!confirm(`Delete “${project.title}”?`)) return
    await props.api(`/portfolio/projects/${project.id}`, { method: 'DELETE' })
    if (editing.value?.id === project.id) editing.value = null
    await load()
    toast.success('Project deleted')
}

onMounted(load)
</script>

<template>
    <section class="space-y-4">
        <div>
            <h1 class="text-3xl font-semibold tracking-tight">Portfolio</h1>
            <p class="text-muted-foreground text-sm">
                Cover thumbnails for Selected work, plus gallery and logos/icons on each project page.
            </p>
        </div>

        <Card class="max-w-2xl">
            <CardHeader>
                <CardTitle>Categories</CardTitle>
            </CardHeader>
            <CardContent class="space-y-3">
                <div class="flex flex-wrap gap-2">
                    <Badge v-for="cat in categories" :key="cat.id" variant="secondary">{{ cat.name }}</Badge>
                    <span v-if="categories.length === 0" class="text-muted-foreground text-sm">No categories yet.</span>
                </div>
                <div class="flex gap-2">
                    <Input v-model="newCategory" placeholder="New category name" />
                    <Button variant="outline" @click="createCategory">Add</Button>
                </div>
            </CardContent>
        </Card>

        <Card class="max-w-2xl">
            <CardHeader>
                <CardTitle>New project</CardTitle>
            </CardHeader>
            <CardContent class="space-y-3">
                <div class="space-y-2"><Label>Title</Label><Input v-model="form.title" /></div>
                <div class="space-y-2"><Label>Summary</Label><Textarea v-model="form.summary" rows="2" /></div>
                <div class="space-y-2"><Label>Case study</Label><Textarea v-model="form.case_study" rows="3" /></div>

                <div class="space-y-2">
                    <Label>Cover / thumbnail</Label>
                    <div v-if="form.cover_image" class="overflow-hidden rounded-lg border">
                        <img :src="form.cover_image" alt="" class="h-36 w-full object-cover">
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <Input v-model="form.cover_image" placeholder="/storage/… or https://" class="min-w-[12rem] flex-1" />
                        <Button type="button" variant="outline" class="gap-1" @click="openMedia('create-cover')">
                            <ImageIcon class="size-3.5" />
                            Library
                        </Button>
                    </div>
                </div>

                <div class="space-y-2">
                    <div class="flex items-center justify-between gap-2">
                        <Label>Gallery (project page)</Label>
                        <Button size="sm" variant="outline" class="gap-1" @click="form.gallery = [...form.gallery, { src: '', alt: '' }]; openMedia('create-gallery', form.gallery.length - 1)">
                            <Plus class="size-3.5" />
                            Add image
                        </Button>
                    </div>
                    <div v-for="(item, index) in form.gallery" :key="`g-${index}`" class="space-y-2 rounded-lg border p-3">
                        <div class="flex justify-end">
                            <Button size="sm" variant="ghost" class="text-destructive h-7 px-2" @click="form.gallery = form.gallery.filter((_, i) => i !== index)">
                                <Trash2 class="size-3.5" />
                            </Button>
                        </div>
                        <div v-if="item.src" class="overflow-hidden rounded border">
                            <img :src="item.src" :alt="item.alt" class="h-28 w-full object-cover">
                        </div>
                        <Button size="sm" variant="outline" class="w-full gap-1" @click="openMedia('create-gallery', index)">
                            <ImageIcon class="size-3.5" />
                            {{ item.src ? 'Replace image' : 'Choose image' }}
                        </Button>
                        <Input v-model="item.alt" placeholder="Caption / alt text" />
                    </div>
                </div>

                <div class="space-y-2">
                    <div class="flex items-center justify-between gap-2">
                        <Label>Logos & icons</Label>
                        <Button size="sm" variant="outline" class="gap-1" @click="form.logos = [...form.logos, { label: '', icon: '', image: '', url: '' }]">
                            <Plus class="size-3.5" />
                            Add
                        </Button>
                    </div>
                    <p class="text-muted-foreground text-xs">Brand icons (Simple Icons) or custom logo images for tools/clients used on this project.</p>
                    <div v-for="(item, index) in form.logos" :key="`l-${index}`" class="space-y-2 rounded-lg border p-3">
                        <div class="flex items-center justify-between gap-2">
                            <div class="flex flex-wrap gap-2">
                                <Button size="sm" variant="outline" class="gap-1" @click="openIconPicker('create', index)">
                                    <SocialBrandIcon v-if="item.icon" :slug="item.icon" :size="14" />
                                    {{ item.icon || 'Pick icon' }}
                                </Button>
                                <Button size="sm" variant="outline" class="gap-1" @click="openMedia('create-logo', index)">
                                    <ImageIcon class="size-3.5" />
                                    Logo image
                                </Button>
                            </div>
                            <Button size="sm" variant="ghost" class="text-destructive h-7 px-2" @click="form.logos = form.logos.filter((_, i) => i !== index)">
                                <Trash2 class="size-3.5" />
                            </Button>
                        </div>
                        <div v-if="item.image" class="bg-muted/40 flex h-12 items-center justify-center rounded border p-2">
                            <img :src="item.image" alt="" class="max-h-full object-contain">
                        </div>
                        <Input v-model="item.label" placeholder="Label (React, Acme Corp…)" />
                        <Input v-model="item.url" placeholder="Optional link https://" />
                    </div>
                </div>

                <div class="grid gap-3 sm:grid-cols-2">
                    <div class="space-y-2"><Label>URL</Label><Input v-model="form.url" placeholder="https://" /></div>
                    <div class="space-y-2"><Label>Skills (comma-separated)</Label><Input v-model="form.skills" placeholder="Laravel, Vue" /></div>
                    <div class="space-y-2 sm:col-span-2">
                        <Label>Category</Label>
                        <select v-model="form.category_id" class="border-input bg-background flex h-9 w-full rounded-md border px-3 text-sm">
                            <option :value="null">None</option>
                            <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                        </select>
                    </div>
                </div>
                <label class="flex items-center gap-2 text-sm">
                    <input v-model="form.is_featured" type="checkbox" class="size-4">
                    Featured on homepage grids
                </label>
                <Button class="gap-2" @click="create"><Plus class="size-4" />Create project</Button>
            </CardContent>
        </Card>

        <Card v-if="editing" class="max-w-2xl border-primary">
            <CardHeader>
                <CardTitle>Edit {{ editing.title }}</CardTitle>
            </CardHeader>
            <CardContent class="space-y-3">
                <div class="space-y-2"><Label>Title</Label><Input v-model="editing.title" /></div>
                <div class="space-y-2"><Label>Summary</Label><Textarea v-model="editing.summary" rows="2" /></div>
                <div class="space-y-2"><Label>Case study</Label><Textarea v-model="editing.case_study" rows="4" /></div>

                <div class="space-y-2">
                    <Label>Cover / thumbnail</Label>
                    <div v-if="editing.cover_image" class="overflow-hidden rounded-lg border">
                        <img :src="editing.cover_image" alt="" class="h-36 w-full object-cover">
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <Input v-model="editing.cover_image" placeholder="/storage/… or https://" class="min-w-[12rem] flex-1" />
                        <Button type="button" variant="outline" class="gap-1" @click="openMedia('edit-cover')">
                            <ImageIcon class="size-3.5" />
                            Library
                        </Button>
                    </div>
                </div>

                <div class="space-y-2">
                    <div class="flex items-center justify-between gap-2">
                        <Label>Gallery (project page)</Label>
                        <Button size="sm" variant="outline" class="gap-1" @click="editing.gallery = [...editing.gallery, { src: '', alt: '' }]; openMedia('edit-gallery', editing.gallery.length - 1)">
                            <Plus class="size-3.5" />
                            Add image
                        </Button>
                    </div>
                    <div v-for="(item, index) in editing.gallery" :key="`eg-${index}`" class="space-y-2 rounded-lg border p-3">
                        <div class="flex justify-end">
                            <Button size="sm" variant="ghost" class="text-destructive h-7 px-2" @click="editing.gallery = editing.gallery.filter((_, i) => i !== index)">
                                <Trash2 class="size-3.5" />
                            </Button>
                        </div>
                        <div v-if="item.src" class="overflow-hidden rounded border">
                            <img :src="item.src" :alt="item.alt" class="h-28 w-full object-cover">
                        </div>
                        <Button size="sm" variant="outline" class="w-full gap-1" @click="openMedia('edit-gallery', index)">
                            <ImageIcon class="size-3.5" />
                            {{ item.src ? 'Replace image' : 'Choose image' }}
                        </Button>
                        <Input v-model="item.alt" placeholder="Caption / alt text" />
                    </div>
                </div>

                <div class="space-y-2">
                    <div class="flex items-center justify-between gap-2">
                        <Label>Logos & icons</Label>
                        <Button size="sm" variant="outline" class="gap-1" @click="editing.logos = [...editing.logos, { label: '', icon: '', image: '', url: '' }]">
                            <Plus class="size-3.5" />
                            Add
                        </Button>
                    </div>
                    <div v-for="(item, index) in editing.logos" :key="`el-${index}`" class="space-y-2 rounded-lg border p-3">
                        <div class="flex items-center justify-between gap-2">
                            <div class="flex flex-wrap gap-2">
                                <Button size="sm" variant="outline" class="gap-1" @click="openIconPicker('edit', index)">
                                    <SocialBrandIcon v-if="item.icon" :slug="item.icon" :size="14" />
                                    {{ item.icon || 'Pick icon' }}
                                </Button>
                                <Button size="sm" variant="outline" class="gap-1" @click="openMedia('edit-logo', index)">
                                    <ImageIcon class="size-3.5" />
                                    Logo image
                                </Button>
                            </div>
                            <Button size="sm" variant="ghost" class="text-destructive h-7 px-2" @click="editing.logos = editing.logos.filter((_, i) => i !== index)">
                                <Trash2 class="size-3.5" />
                            </Button>
                        </div>
                        <div v-if="item.image" class="bg-muted/40 flex h-12 items-center justify-center rounded border p-2">
                            <img :src="item.image" alt="" class="max-h-full object-contain">
                        </div>
                        <Input v-model="item.label" placeholder="Label" />
                        <Input v-model="item.url" placeholder="Optional link https://" />
                    </div>
                </div>

                <div class="grid gap-3 sm:grid-cols-2">
                    <div class="space-y-2"><Label>URL</Label><Input v-model="editing.url" /></div>
                    <div class="space-y-2"><Label>Skills</Label><Input v-model="editing.skills" /></div>
                </div>
                <div class="space-y-2">
                    <Label>Category</Label>
                    <select v-model="editing.category_id" class="border-input bg-background flex h-9 w-full rounded-md border px-3 text-sm">
                        <option :value="null">None</option>
                        <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                    </select>
                </div>
                <div class="flex gap-2">
                    <Button @click="saveEdit">Save changes</Button>
                    <Button variant="ghost" @click="editing = null">Cancel</Button>
                </div>
            </CardContent>
        </Card>

        <Card>
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead class="w-16" />
                        <TableHead>Title</TableHead>
                        <TableHead>Media</TableHead>
                        <TableHead>Status</TableHead>
                        <TableHead>Featured</TableHead>
                        <TableHead />
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow v-if="projects.length === 0">
                        <TableCell colspan="6" class="text-muted-foreground py-8 text-center">No projects yet.</TableCell>
                    </TableRow>
                    <TableRow v-for="project in projects" :key="project.id">
                        <TableCell>
                            <div class="bg-muted size-12 overflow-hidden rounded-md border">
                                <img v-if="project.cover_image" :src="project.cover_image" alt="" class="size-full object-cover">
                            </div>
                        </TableCell>
                        <TableCell>
                            <p class="font-medium">{{ project.title }}</p>
                            <p class="text-muted-foreground text-xs">/projects/{{ project.slug }}</p>
                        </TableCell>
                        <TableCell class="text-muted-foreground text-xs">
                            {{ asGallery(project.gallery).length }} gallery · {{ asLogos(project.logos).length }} logos
                        </TableCell>
                        <TableCell><Badge>{{ project.status }} / {{ project.visibility }}</Badge></TableCell>
                        <TableCell>{{ project.is_featured ? 'Yes' : 'No' }}</TableCell>
                        <TableCell class="space-x-1">
                            <Button size="sm" variant="outline" class="gap-1" as-child>
                                <a :href="`/projects/${project.slug}`" target="_blank" rel="noopener">
                                    <ExternalLink class="size-3.5" />
                                </a>
                            </Button>
                            <Button size="sm" variant="outline" @click="startEdit(project)">Edit</Button>
                            <Button size="sm" variant="outline" @click="toggleFeatured(project)">Featured</Button>
                            <Button
                                size="sm"
                                variant="ghost"
                                @click="setStatus(project, project.status === 'published' ? 'draft' : 'published')"
                            >
                                {{ project.status === 'published' ? 'Unpublish' : 'Publish' }}
                            </Button>
                            <Button size="sm" variant="destructive" @click="remove(project)"><Trash2 class="size-3.5" /></Button>
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </Card>

        <div
            v-if="showMediaPicker"
            class="fixed inset-0 z-[80] flex items-center justify-center bg-black/50 p-4"
            @click.self="showMediaPicker = false"
        >
            <div class="bg-background max-h-[85vh] w-full max-w-4xl overflow-auto rounded-xl border p-4 shadow-xl">
                <div class="mb-3 flex items-center justify-between gap-2">
                    <strong>Choose media</strong>
                    <Button size="sm" variant="ghost" @click="showMediaPicker = false">
                        <X class="size-4" />
                    </Button>
                </div>
                <MediaPanel
                    :items="mediaFiles"
                    :csrf="csrf"
                    :api="api"
                    selectable
                    @refreshed="mediaFiles = $event"
                    @select="pickMedia"
                />
            </div>
        </div>

        <IconPicker
            v-model:open="iconPickerOpen"
            :model-value="(iconTarget === 'edit' ? editing?.logos[iconIndex]?.icon : form.logos[iconIndex]?.icon) || null"
            title="Project logo icon"
            @update:model-value="onIconPicked"
        />
    </section>
</template>
