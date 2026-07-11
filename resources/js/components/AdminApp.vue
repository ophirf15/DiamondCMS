<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { VueDraggable } from 'vue-draggable-plus'
import {
    ArrowLeft,
    Briefcase,
    ExternalLink,
    Eye,
    FileText,
    Images,
    LayoutDashboard,
    LayoutTemplate,
    LogOut,
    Palette,
    Pencil,
    Plus,
    Redo2,
    Save,
    Undo2,
    Upload,
    Trash2,
    X,
} from '@lucide/vue'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Badge } from '@/components/ui/badge'
import { Separator } from '@/components/ui/separator'
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table'
import { ScrollArea } from '@/components/ui/scroll-area'
import { Toaster } from '@/components/ui/sonner'
import { toast } from 'vue-sonner'
import BuilderBlockView, { type BuilderBlock } from '@/components/builder/BuilderBlockView.vue'
import { SECTION_KITS } from '@/components/builder/sectionKits'

const brandLogo = '/brand/logo-primary-gold.svg'

type BlockType = string
type BuilderDocument = {
    schema: number
    title: string
    blocks: BuilderBlock[]
}

type Page = {
    id: number
    title: string
    slug: string
    status: string
    builder_json: string | BuilderDocument | null
}

type RegistryBlock = {
    type: BlockType
    label: string
    defaults: Record<string, string | number | boolean>
}

type Dashboard = {
    pages: number
    published: number
    drafts: number
    media: number
}

type TemplateRow = {
    id: number
    name: string
    slug: string
    category: string
    builder_json: string | BuilderDocument
}

type NavId = 'dashboard' | 'pages' | 'media' | 'design' | 'resumes'
type EditorMode = 'browse' | 'edit'

const csrf = document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? ''
const activePanel = ref<NavId>('pages')
const mode = ref<EditorMode>('browse')
const dashboard = ref<Dashboard>({ pages: 0, published: 0, drafts: 0, media: 0 })
const pages = ref<Page[]>([])
const registry = ref<RegistryBlock[]>([])
const selectedPage = ref<Page | null>(null)
const documentState = ref<BuilderDocument>(emptyDocument('Untitled page'))
const selectedBlock = ref<BuilderBlock | null>(null)
const history = ref<BuilderDocument[]>([])
const future = ref<BuilderDocument[]>([])
const mediaFiles = ref<unknown[]>([])
const templates = ref<TemplateRow[]>([])
const resumeName = ref('')
const showNewPage = ref(false)
const newPageTitle = ref('')
const creatingPage = ref(false)
const saving = ref(false)

const TEMPLATE_META: Record<string, { blurb: string, gradient: string }> = {
    'dark-technical-resume': { blurb: 'Dark sidebar résumé with strong typography.', gradient: 'linear-gradient(135deg,#0f172a,#134e4a)' },
    'minimal-professional-resume': { blurb: 'Clean one-column résumé for traditional roles.', gradient: 'linear-gradient(135deg,#f8fafc,#d1fae5)' },
    'creative-portfolio': { blurb: 'Bold hero and project grid for creative work.', gradient: 'linear-gradient(135deg,#1a221e,#a67c3d)' },
    'property-management-professional': { blurb: 'Service-focused landing for PM professionals.', gradient: 'linear-gradient(135deg,#ecfdf5,#0d5c4d)' },
    'developer-technical-portfolio': { blurb: 'Project-led site for engineers and builders.', gradient: 'linear-gradient(135deg,#111827,#3d9b82)' },
    'photography-portfolio': { blurb: 'Image-forward gallery layout.', gradient: 'linear-gradient(135deg,#1c1917,#78716c)' },
    'personal-biography': { blurb: 'Story-first about page with timeline sections.', gradient: 'linear-gradient(135deg,#fafaf9,#a8a29e)' },
    'split-screen-resume': { blurb: 'Split profile and experience layout.', gradient: 'linear-gradient(90deg,#0d5c4d 50%,#f6f8f6 50%)' },
    'editorial-case-study-portfolio': { blurb: 'Long-form case study storytelling.', gradient: 'linear-gradient(135deg,#f5f5f4,#57534e)' },
    'modern-one-page-personal-site': { blurb: 'Single-page personal site with CTA sections.', gradient: 'linear-gradient(135deg,#e4e9e5,#0d5c4d)' },
}

const navItems: { id: NavId, label: string, icon: typeof LayoutDashboard }[] = [
    { id: 'dashboard', label: 'Dashboard', icon: LayoutDashboard },
    { id: 'pages', label: 'Pages', icon: FileText },
    { id: 'media', label: 'Media', icon: Images },
    { id: 'design', label: 'Templates', icon: Palette },
    { id: 'resumes', label: 'Resumes', icon: Briefcase },
]

const friendlyBlocks = computed(() => registry.value.filter((block) => !['html'].includes(block.type)))
const leftTab = ref<'blocks' | 'sections'>('sections')
const pageTitle = computed({
    get: () => selectedPage.value?.title ?? documentState.value.title,
    set: (value: string) => {
        if (selectedPage.value) selectedPage.value.title = value
        documentState.value.title = value
    },
})

function emptyDocument(title: string): BuilderDocument {
    return {
        schema: 1,
        title,
        blocks: [SECTION_KITS[0].build()],
    }
}

function slugify(title: string): string {
    return title.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '') || 'page'
}

async function api<T>(url: string, options: RequestInit = {}): Promise<T> {
    const response = await fetch(`/admin/api${url}`, {
        headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrf, ...(options.headers ?? {}) },
        ...options,
    })
    if (!response.ok) throw new Error(await response.text())
    return (await response.json()) as T
}

function snapshot(): void {
    history.value.push(JSON.parse(JSON.stringify(documentState.value)) as BuilderDocument)
    future.value = []
    localStorage.setItem('diamondcms.builder.recovery', JSON.stringify({ pageId: selectedPage.value?.id ?? null, document: documentState.value }))
}

function undo(): void {
    const previous = history.value.pop()
    if (!previous) return
    future.value.push(JSON.parse(JSON.stringify(documentState.value)) as BuilderDocument)
    documentState.value = previous
}

function redo(): void {
    const next = future.value.pop()
    if (!next) return
    history.value.push(JSON.parse(JSON.stringify(documentState.value)) as BuilderDocument)
    documentState.value = next
}

function blockLabel(block: BuilderBlock): string {
    return registry.value.find((candidate) => candidate.type === block.type)?.label ?? block.type
}

function ensureChildren(blocks: BuilderBlock[]): BuilderBlock[] {
    return blocks.map((block) => {
        if (block.type === 'section' || block.type === 'columns') {
            block.children = ensureChildren(block.children ?? [])
        }
        return block
    })
}

function parsePageDocument(page: Page): BuilderDocument {
    const raw = typeof page.builder_json === 'string' ? JSON.parse(page.builder_json) as BuilderDocument : page.builder_json
    const document = raw ?? emptyDocument(page.title)
    document.blocks = ensureChildren(document.blocks ?? [])
    return document
}

function parseTemplateDocument(template: TemplateRow): BuilderDocument {
    const raw = typeof template.builder_json === 'string' ? JSON.parse(template.builder_json) as BuilderDocument : template.builder_json
    raw.blocks = ensureChildren(raw.blocks ?? [])
    return raw
}

function templateMeta(template: TemplateRow) {
    return TEMPLATE_META[template.slug] ?? {
        blurb: 'Starter layout you can customize visually.',
        gradient: 'linear-gradient(135deg,#e4e9e5,#0d5c4d)',
    }
}

function addBlock(block: RegistryBlock): void {
    snapshot()
    const created: BuilderBlock = {
        id: crypto.randomUUID(),
        type: block.type,
        props: { ...block.defaults },
        children: block.type === 'section' || block.type === 'columns' ? [] : undefined,
    }
    if (selectedBlock.value && (selectedBlock.value.type === 'section' || selectedBlock.value.type === 'columns')) {
        selectedBlock.value.children = selectedBlock.value.children ?? []
        selectedBlock.value.children.push(created)
    }
    else {
        documentState.value.blocks.push(created)
    }
    selectedBlock.value = created
}

function addSectionKit(kitId: string): void {
    const kit = SECTION_KITS.find((item) => item.id === kitId)
    if (!kit) return
    snapshot()
    const created = kit.build()
    documentState.value.blocks.push(created)
    selectedBlock.value = created
}

function addChildTo(parent: BuilderBlock, type: string): void {
    const defaults = registry.value.find((item) => item.type === type)?.defaults
        ?? (type === 'heading' ? { level: 2, text: 'Heading' } : { text: 'New content' })
    snapshot()
    parent.children = parent.children ?? []
    const created: BuilderBlock = {
        id: crypto.randomUUID(),
        type,
        props: { ...defaults },
        children: type === 'section' || type === 'columns' ? [] : undefined,
    }
    parent.children.push(created)
    selectedBlock.value = created
}

function removeBlockFromTree(list: BuilderBlock[], target: BuilderBlock): boolean {
    const index = list.findIndex((item) => item.id === target.id)
    if (index >= 0) {
        list.splice(index, 1)
        return true
    }
    for (const item of list) {
        if (item.children && removeBlockFromTree(item.children, target)) return true
    }
    return false
}

function deleteBlock(block: BuilderBlock): void {
    snapshot()
    removeBlockFromTree(documentState.value.blocks, block)
    selectedBlock.value = null
}

async function openPreview(): Promise<void> {
    if (!selectedPage.value) return
    try {
        await savePage()
        const result = await api<{ url: string }>(`/pages/${selectedPage.value.id}/preview-token`, {
            method: 'POST',
            body: '{}',
        })
        window.open(result.url, '_blank', 'noopener')
    }
    catch (error) {
        toast.error(error instanceof Error ? error.message : 'Could not open preview')
    }
}

async function load(): Promise<void> {
    try {
        dashboard.value = await api<Dashboard>('/dashboard')
        pages.value = (await api<{ data: Page[] }>('/pages')).data
        registry.value = (await api<{ blocks: RegistryBlock[] }>('/builder/registry')).blocks
        mediaFiles.value = (await api<{ data: unknown[] }>('/media')).data
        templates.value = await api<TemplateRow[]>('/templates')
    }
    catch (error) {
        toast.error(error instanceof Error ? error.message : 'Could not load admin')
    }
}

function openEditor(page: Page): void {
    selectedPage.value = page
    documentState.value = parsePageDocument(page)
    selectedBlock.value = null
    history.value = []
    future.value = []
    mode.value = 'edit'
    activePanel.value = 'pages'
}

function closeEditor(): void {
    mode.value = 'browse'
    selectedPage.value = null
    selectedBlock.value = null
}

async function createPage(fromTemplate?: TemplateRow): Promise<void> {
    const title = newPageTitle.value.trim() || fromTemplate?.name || 'Untitled page'
    creatingPage.value = true
    try {
        const builder = fromTemplate ? parseTemplateDocument(fromTemplate) : emptyDocument(title)
        builder.title = title
        const page = await api<Page>('/pages', {
            method: 'POST',
            body: JSON.stringify({
                title,
                slug: slugify(title),
                status: 'draft',
                builder_json: builder,
            }),
        })
        pages.value.unshift(page)
        showNewPage.value = false
        newPageTitle.value = ''
        openEditor(page)
        toast.success('Page created — start editing')
        await refreshCounts()
    }
    catch (error) {
        toast.error(error instanceof Error ? error.message : 'Could not create page')
    }
    finally {
        creatingPage.value = false
    }
}

async function savePage(statusOverride?: string): Promise<void> {
    if (!selectedPage.value) return
    saving.value = true
    snapshot()
    try {
        const page = await api<Page>(`/pages/${selectedPage.value.id}`, {
            method: 'PUT',
            body: JSON.stringify({
                title: selectedPage.value.title,
                slug: selectedPage.value.slug || slugify(selectedPage.value.title),
                status: statusOverride ?? selectedPage.value.status,
                builder_json: documentState.value,
            }),
        })
        selectedPage.value = page
        pages.value = pages.value.map((candidate) => (candidate.id === page.id ? page : candidate))
        localStorage.removeItem('diamondcms.builder.recovery')
        toast.success(statusOverride === 'published' ? 'Published' : 'Draft saved')
        await refreshCounts()
    }
    catch (error) {
        toast.error(error instanceof Error ? error.message : 'Could not save page')
    }
    finally {
        saving.value = false
    }
}

async function refreshCounts(): Promise<void> {
    dashboard.value = await api<Dashboard>('/dashboard')
}

async function uploadMedia(event: Event): Promise<void> {
    const input = event.target as HTMLInputElement
    const file = input.files?.[0]
    if (!file) return
    const form = new FormData()
    form.append('file', file)
    const response = await fetch('/admin/api/media', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrf, Accept: 'application/json' },
        body: form,
    })
    if (!response.ok) {
        toast.error(await response.text())
        return
    }
    mediaFiles.value = (await api<{ data: unknown[] }>('/media')).data
    toast.success('Uploaded')
}

async function seedTemplates(): Promise<void> {
    await api('/templates/seed', { method: 'POST', body: '{}' })
    templates.value = await api<TemplateRow[]>('/templates')
    toast.success('Starter templates ready')
}

async function useTemplate(template: TemplateRow): Promise<void> {
    newPageTitle.value = template.name
    showNewPage.value = false
    await createPage(template)
}

async function createResume(): Promise<void> {
    if (!resumeName.value.trim()) return
    await api('/resumes', { method: 'POST', body: JSON.stringify({ name: resumeName.value.trim() }) })
    resumeName.value = ''
    toast.success('Resume profile created')
}

function openNewPageDialog(): void {
    newPageTitle.value = ''
    showNewPage.value = true
}

watch(activePanel, (panel) => {
    if (panel !== 'pages') mode.value = 'browse'
})

onMounted(load)
</script>

<template>
    <div class="bg-background text-foreground flex min-h-screen">
        <Toaster rich-colors position="bottom-right" close-button />

        <aside
            v-if="mode === 'browse'"
            class="border-sidebar-border bg-sidebar text-sidebar-foreground flex w-64 shrink-0 flex-col border-r"
        >
            <div class="flex items-center gap-2 px-4 py-5">
                <img :src="brandLogo" alt="" class="size-7 shrink-0">
                <div>
                    <p class="font-semibold tracking-tight">DiamondCMS</p>
                    <p class="text-muted-foreground text-xs">Website studio</p>
                </div>
            </div>
            <Separator />
            <nav class="flex flex-1 flex-col gap-1 p-3">
                <Button
                    v-for="item in navItems"
                    :key="item.id"
                    :variant="activePanel === item.id ? 'secondary' : 'ghost'"
                    class="h-9 w-full justify-start gap-2 px-3"
                    @click="activePanel = item.id"
                >
                    <component :is="item.icon" class="size-4 shrink-0" />
                    <span>{{ item.label }}</span>
                </Button>
            </nav>
            <div class="p-3">
                <form method="post" action="/logout">
                    <input type="hidden" name="_token" :value="csrf">
                    <Button type="submit" variant="outline" class="h-9 w-full justify-start gap-2 px-3">
                        <LogOut class="size-4 shrink-0" />
                        <span>Log out</span>
                    </Button>
                </form>
            </div>
        </aside>

        <!-- Browse mode -->
        <main v-if="mode === 'browse'" class="flex min-w-0 flex-1 flex-col gap-6 p-6">
            <section v-if="activePanel === 'dashboard'" class="space-y-6">
                <div>
                    <h1 class="text-3xl font-semibold tracking-tight">Welcome back</h1>
                    <p class="text-muted-foreground mt-1 text-sm">Build and publish your personal site without touching code.</p>
                </div>
                <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <Card v-for="stat in [
                        { label: 'Pages', value: dashboard.pages },
                        { label: 'Published', value: dashboard.published },
                        { label: 'Drafts', value: dashboard.drafts },
                        { label: 'Media', value: dashboard.media },
                    ]" :key="stat.label">
                        <CardHeader class="pb-2">
                            <CardDescription>{{ stat.label }}</CardDescription>
                            <CardTitle class="text-3xl">{{ stat.value }}</CardTitle>
                        </CardHeader>
                    </Card>
                </div>
                <Card>
                    <CardHeader>
                        <CardTitle>Quick start</CardTitle>
                        <CardDescription>Most people begin by creating a Home page, then picking a template.</CardDescription>
                    </CardHeader>
                    <CardContent class="flex flex-wrap gap-2">
                        <Button class="gap-2" @click="activePanel = 'pages'; openNewPageDialog()">
                            <Plus class="size-4" />
                            <span>New page</span>
                        </Button>
                        <Button variant="outline" class="gap-2" @click="activePanel = 'design'">
                            <Palette class="size-4" />
                            <span>Browse templates</span>
                        </Button>
                    </CardContent>
                </Card>
            </section>

            <section v-else-if="activePanel === 'pages'" class="space-y-4">
                <div class="flex flex-wrap items-end justify-between gap-3">
                    <div>
                        <h1 class="text-3xl font-semibold tracking-tight">Pages</h1>
                        <p class="text-muted-foreground text-sm">Open a page to edit it visually. The builder lives inside each page.</p>
                    </div>
                    <Button class="gap-2" @click="openNewPageDialog">
                        <Plus class="size-4" />
                        <span>New page</span>
                    </Button>
                </div>
                <Card>
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Title</TableHead>
                                <TableHead>Address</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead class="w-[140px]" />
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-if="pages.length === 0">
                                <TableCell colspan="4" class="text-muted-foreground py-10 text-center">
                                    No pages yet. Create your first page to open the visual editor.
                                </TableCell>
                            </TableRow>
                            <TableRow v-for="page in pages" :key="page.id">
                                <TableCell class="font-medium">{{ page.title }}</TableCell>
                                <TableCell class="text-muted-foreground">/{{ page.slug }}</TableCell>
                                <TableCell>
                                    <Badge :variant="page.status === 'published' ? 'default' : 'secondary'">{{ page.status }}</Badge>
                                </TableCell>
                                <TableCell>
                                    <Button size="sm" variant="outline" class="gap-1.5" @click="openEditor(page)">
                                        <Pencil class="size-3.5" />
                                        <span>Edit</span>
                                    </Button>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </Card>
            </section>

            <section v-else-if="activePanel === 'media'" class="space-y-4">
                <div>
                    <h1 class="text-3xl font-semibold tracking-tight">Media</h1>
                    <p class="text-muted-foreground text-sm">Upload images and files you can place on pages.</p>
                </div>
                <Card>
                    <CardContent class="space-y-4 pt-6">
                        <Input type="file" accept="image/*,.pdf" @change="uploadMedia" />
                        <p class="text-muted-foreground text-sm">{{ mediaFiles.length }} file(s) in your library</p>
                    </CardContent>
                </Card>
            </section>

            <section v-else-if="activePanel === 'design'" class="space-y-4">
                <div class="flex flex-wrap items-end justify-between gap-3">
                    <div>
                        <h1 class="text-3xl font-semibold tracking-tight">Templates</h1>
                        <p class="text-muted-foreground text-sm">Preview a look, then start a new editable page from it.</p>
                    </div>
                    <Button variant="outline" @click="seedTemplates">Refresh starter set</Button>
                </div>
                <div v-if="templates.length === 0" class="rounded-xl border border-dashed p-10 text-center">
                    <p class="text-muted-foreground mb-4 text-sm">No templates installed yet.</p>
                    <Button @click="seedTemplates">Install starter templates</Button>
                </div>
                <div v-else class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    <Card v-for="template in templates" :key="template.id" class="overflow-hidden">
                        <div class="h-36 w-full" :style="{ background: templateMeta(template).gradient }" />
                        <CardHeader class="space-y-2">
                            <div class="flex items-center justify-between gap-2">
                                <CardTitle class="text-base">{{ template.name }}</CardTitle>
                                <Badge variant="secondary">{{ template.category }}</Badge>
                            </div>
                            <CardDescription>{{ templateMeta(template).blurb }}</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <Button class="w-full gap-2" @click="useTemplate(template)">
                                <Eye class="size-4" />
                                <span>Use this template</span>
                            </Button>
                        </CardContent>
                    </Card>
                </div>
            </section>

            <section v-else class="space-y-4">
                <div>
                    <h1 class="text-3xl font-semibold tracking-tight">Resumes</h1>
                    <p class="text-muted-foreground text-sm">Create a résumé profile you can place on pages.</p>
                </div>
                <Card class="max-w-lg">
                    <CardContent class="space-y-4 pt-6">
                        <div class="space-y-2">
                            <Label for="resume-name">Profile name</Label>
                            <Input id="resume-name" v-model="resumeName" placeholder="Primary résumé" />
                        </div>
                        <Button class="gap-2" @click="createResume">
                            <Plus class="size-4" />
                            <span>Create profile</span>
                        </Button>
                    </CardContent>
                </Card>
            </section>
        </main>

        <!-- Full page editor -->
        <div v-else class="flex min-h-screen min-w-0 flex-1 flex-col">
            <header class="bg-background/95 supports-backdrop-filter:bg-background/80 sticky top-0 z-20 flex items-center gap-3 border-b px-4 py-3 backdrop-blur">
                <Button variant="ghost" size="sm" class="gap-1.5" @click="closeEditor">
                    <ArrowLeft class="size-4" />
                    <span>Pages</span>
                </Button>
                <Separator orientation="vertical" class="h-6" />
                <Input v-model="pageTitle" class="h-9 max-w-sm font-medium" aria-label="Page title" />
                <Badge variant="secondary">{{ selectedPage?.status ?? 'draft' }}</Badge>
                <div class="ml-auto flex flex-wrap items-center gap-1.5">
                    <Button size="sm" variant="outline" class="gap-1.5" @click="undo"><Undo2 class="size-4" /><span>Undo</span></Button>
                    <Button size="sm" variant="outline" class="gap-1.5" @click="redo"><Redo2 class="size-4" /><span>Redo</span></Button>
                    <Button size="sm" variant="outline" class="gap-1.5" :disabled="saving" @click="savePage()">
                        <Save class="size-4" />
                        <span>Save draft</span>
                    </Button>
                    <Button size="sm" class="gap-1.5" :disabled="saving" @click="savePage('published')">
                        <Upload class="size-4" />
                        <span>Publish</span>
                    </Button>
                    <Button size="sm" variant="outline" class="gap-1.5" @click="openPreview">
                        <ExternalLink class="size-4" />
                        <span>Preview</span>
                    </Button>
                </div>
            </header>

            <div class="grid min-h-0 flex-1 grid-cols-1 xl:grid-cols-[280px_minmax(0,1fr)_300px]">
                <aside class="bg-muted/20 hidden border-r xl:block">
                    <div class="space-y-3 p-4">
                        <div class="grid grid-cols-2 gap-1 rounded-lg border p-1">
                            <Button
                                size="sm"
                                :variant="leftTab === 'sections' ? 'secondary' : 'ghost'"
                                class="h-8"
                                @click="leftTab = 'sections'"
                            >
                                Sections
                            </Button>
                            <Button
                                size="sm"
                                :variant="leftTab === 'blocks' ? 'secondary' : 'ghost'"
                                class="h-8"
                                @click="leftTab = 'blocks'"
                            >
                                Blocks
                            </Button>
                        </div>
                        <ScrollArea class="h-[calc(100vh-9rem)]">
                            <div v-if="leftTab === 'sections'" class="space-y-2 pr-2">
                                <p class="text-muted-foreground px-1 text-xs">
                                    Drop ready-made sections onto the page — like Elementor kits.
                                </p>
                                <button
                                    v-for="kit in SECTION_KITS"
                                    :key="kit.id"
                                    type="button"
                                    class="hover:border-primary/40 hover:bg-background w-full rounded-xl border bg-card p-3 text-left transition"
                                    @click="addSectionKit(kit.id)"
                                >
                                    <div class="mb-2 flex items-center gap-2">
                                        <LayoutTemplate class="text-primary size-4" />
                                        <span class="text-sm font-semibold">{{ kit.label }}</span>
                                    </div>
                                    <p class="text-muted-foreground text-xs leading-relaxed">{{ kit.description }}</p>
                                </button>
                            </div>
                            <div v-else class="space-y-1.5 pr-2">
                                <p class="text-muted-foreground px-1 pb-1 text-xs">
                                    Select a section first to drop blocks inside it, or add to the page root.
                                </p>
                                <Button
                                    v-for="block in friendlyBlocks"
                                    :key="block.type"
                                    variant="outline"
                                    size="sm"
                                    class="h-9 w-full justify-start"
                                    @click="addBlock(block)"
                                >
                                    {{ block.label }}
                                </Button>
                            </div>
                        </ScrollArea>
                    </div>
                </aside>

                <section class="bg-muted/40 min-w-0 overflow-auto p-4 md:p-8">
                    <div class="bg-background mx-auto min-h-[75vh] max-w-4xl rounded-2xl border shadow-sm">
                        <div class="border-b px-6 py-4">
                            <p class="text-muted-foreground text-xs tracking-wide uppercase">Live canvas</p>
                            <h2 class="text-xl font-semibold">{{ pageTitle }}</h2>
                            <p class="text-muted-foreground mt-1 text-sm">Click text to edit inline. Drag sections to reorder.</p>
                        </div>
                        <VueDraggable
                            v-model="documentState.blocks"
                            class="space-y-4 p-4 md:p-8"
                            group="builder-blocks"
                            :animation="180"
                            @end="snapshot"
                        >
                            <BuilderBlockView
                                v-for="block in documentState.blocks"
                                :key="block.id"
                                :block="block"
                                :selected-id="selectedBlock?.id ?? null"
                                @select="selectedBlock = $event"
                                @update="snapshot"
                                @remove="deleteBlock"
                                @add-child="addChildTo"
                            />
                        </VueDraggable>
                        <div v-if="documentState.blocks.length === 0" class="text-muted-foreground p-12 text-center text-sm">
                            Add a section kit from the left to start designing this page.
                        </div>
                    </div>
                </section>

                <aside class="hidden border-l xl:block">
                    <div class="space-y-4 p-4">
                        <div>
                            <p class="mb-1 text-xs font-semibold tracking-wide uppercase">Settings</p>
                            <p class="text-muted-foreground text-sm">
                                {{ selectedBlock ? `Editing “${blockLabel(selectedBlock)}”` : 'Select anything on the canvas to change its content.' }}
                            </p>
                        </div>
                        <template v-if="selectedBlock">
                            <div
                                v-for="(value, key) in selectedBlock.props"
                                :key="key"
                                class="space-y-2"
                            >
                                <Label :for="`prop-${key}`" class="capitalize">{{ String(key).replace(/_/g, ' ') }}</Label>
                                <Input
                                    :id="`prop-${key}`"
                                    :model-value="String(value ?? '')"
                                    @update:model-value="(next) => { selectedBlock!.props[key] = next; snapshot() }"
                                />
                            </div>
                            <Button variant="destructive" size="sm" class="gap-1.5" @click="deleteBlock(selectedBlock)">
                                <Trash2 class="size-3.5" />
                                <span>Remove</span>
                            </Button>
                        </template>
                    </div>
                </aside>
            </div>
        </div>

        <!-- New page modal -->
        <div
            v-if="showNewPage"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"
            @click.self="showNewPage = false"
        >
            <Card class="w-full max-w-md shadow-lg">
                <CardHeader class="relative">
                    <CardTitle>Create a page</CardTitle>
                    <CardDescription>Give it a name — you can change everything in the visual editor next.</CardDescription>
                    <Button variant="ghost" size="icon-sm" class="absolute top-3 right-3" @click="showNewPage = false">
                        <X class="size-4" />
                    </Button>
                </CardHeader>
                <CardContent class="space-y-4">
                    <div class="space-y-2">
                        <Label for="new-page-title">Page title</Label>
                        <Input
                            id="new-page-title"
                            v-model="newPageTitle"
                            placeholder="Home"
                            autofocus
                            @keydown.enter.prevent="createPage()"
                        />
                    </div>
                    <div class="flex justify-end gap-2">
                        <Button variant="outline" @click="showNewPage = false">Cancel</Button>
                        <Button class="gap-2" :disabled="creatingPage" @click="createPage()">
                            <Plus class="size-4" />
                            <span>{{ creatingPage ? 'Creating…' : 'Create & edit' }}</span>
                        </Button>
                    </div>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
