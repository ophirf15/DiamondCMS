<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { VueDraggable } from 'vue-draggable-plus'
import {
    ArrowLeft,
    Briefcase,
    ExternalLink,
    FileText,
    FormInput,
    Images,
    LayoutDashboard,
    LayoutTemplate,
    Layers,
    LogOut,
    Menu,
    Palette,
    Pencil,
    Plus,
    Redo2,
    Save,
    Search,
    Settings,
    Shield,
    Sparkles,
    Server,
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
import ActionToastHost from '@/components/ui/ActionToastHost.vue'
import { toast } from 'vue-sonner'
import { showActionToast } from '@/lib/actionToast'
import { uploadMediaFile } from '@/lib/mediaUpload'
import BuilderBlockView, { type BuilderBlock } from '@/components/builder/BuilderBlockView.vue'
import PublicSiteChrome, { type ChromeConfig, type MenuItem as ChromeMenuItem } from '@/components/builder/PublicSiteChrome.vue'
import { SECTION_KITS } from '@/components/builder/sectionKits'
import SettingsPanel from '@/components/admin/SettingsPanel.vue'
import ThemePanel from '@/components/admin/ThemePanel.vue'
import TemplatesPanel from '@/components/admin/TemplatesPanel.vue'
import MediaPanel, { type MediaItem } from '@/components/admin/MediaPanel.vue'
import TrashPanel from '@/components/admin/TrashPanel.vue'
import MenusPanel from '@/components/admin/MenusPanel.vue'
import FormsPanel from '@/components/admin/FormsPanel.vue'
import AccountPanel from '@/components/admin/AccountPanel.vue'
import ResumesPanel from '@/components/admin/ResumesPanel.vue'
import PortfolioPanel from '@/components/admin/PortfolioPanel.vue'
import SeoPanel from '@/components/admin/SeoPanel.vue'
import AiPanel from '@/components/admin/AiPanel.vue'
import SystemPanel from '@/components/admin/SystemPanel.vue'

const brandLogo = '/brand/logo-primary-gold.svg'

type BlockType = string
type BuilderDocument = {
    schema: number
    title: string
    meta?: { shell?: string; preview_theme?: string; blurb?: string }
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
    defaults: Record<string, unknown>
}

type AnalyticsSummary = {
    page_views_today: number
    page_views_7d: number
    unique_visitors_7d: number
    resume_downloads: number
    resume_downloads_7d: number
    top_pages: { page_id: number | null, path: string, title: string, visits: number }[]
    daily_views: { day: string, visits: number }[]
}

type Dashboard = {
    pages: number
    published: number
    drafts: number
    media: number
    analytics?: AnalyticsSummary
}

type TemplateRow = {
    id: number
    name: string
    slug: string
    category: string
    builder_json: string | BuilderDocument
}

type NavId = 'dashboard' | 'pages' | 'media' | 'templates' | 'theme' | 'menus' | 'forms' | 'settings' | 'resumes' | 'portfolio' | 'account' | 'seo' | 'ai' | 'system' | 'trash'
type EditorMode = 'browse' | 'edit'

const csrf = document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? ''
const activePanel = ref<NavId>('pages')
const mode = ref<EditorMode>('browse')
const emptyAnalytics = (): AnalyticsSummary => ({
    page_views_today: 0,
    page_views_7d: 0,
    unique_visitors_7d: 0,
    resume_downloads: 0,
    resume_downloads_7d: 0,
    top_pages: [],
    daily_views: [],
})

const dashboard = ref<Dashboard>({ pages: 0, published: 0, drafts: 0, media: 0, analytics: emptyAnalytics() })
const pageActionBusy = ref<number | null>(null)
const pages = ref<Page[]>([])
const registry = ref<RegistryBlock[]>([])
const selectedPage = ref<Page | null>(null)
const documentState = ref<BuilderDocument>(emptyDocument('Untitled page'))
const selectedBlock = ref<BuilderBlock | null>(null)
const history = ref<BuilderDocument[]>([])
const future = ref<BuilderDocument[]>([])
const mediaFiles = ref<MediaItem[]>([])
const templates = ref<TemplateRow[]>([])
const showNewPage = ref(false)
const newPageTitle = ref('')
const creatingPage = ref(false)
const saving = ref(false)
const showMediaPicker = ref(false)
const mediaPickerTarget = ref<{ field: 'src' } | { field: 'images', index: number } | null>(null)
const uploadingImage = ref(false)
const siteChrome = ref<ChromeConfig>({
    headerStyle: 'classic',
    footerStyle: 'branded',
    buttonStyle: 'solid',
    footerShowLogo: true,
    footerShowSiteName: true,
    footerTagline: '',
    footerSocials: [],
    footerSocialStyle: 'icons',
})
const siteName = ref('DiamondCMS')
const siteLogo = ref('/brand/logo-primary-gold.svg')
const headerMenu = ref<ChromeMenuItem[]>([])
const footerMenu = ref<ChromeMenuItem[]>([])
const pageShell = computed(() => {
    const meta = documentState.value.meta as { shell?: string } | undefined
    return meta?.shell || 'default'
})

const navItems: { id: NavId, label: string, icon: typeof LayoutDashboard }[] = [
    { id: 'dashboard', label: 'Dashboard', icon: LayoutDashboard },
    { id: 'pages', label: 'Pages', icon: FileText },
    { id: 'templates', label: 'Templates', icon: LayoutTemplate },
    { id: 'theme', label: 'Theme', icon: Palette },
    { id: 'media', label: 'Media', icon: Images },
    { id: 'trash', label: 'Trash', icon: Trash2 },
    { id: 'menus', label: 'Menus', icon: Menu },
    { id: 'forms', label: 'Forms', icon: FormInput },
    { id: 'resumes', label: 'Resumes', icon: Briefcase },
    { id: 'portfolio', label: 'Portfolio', icon: Layers },
    { id: 'seo', label: 'SEO', icon: Search },
    { id: 'ai', label: 'AI', icon: Sparkles },
    { id: 'system', label: 'System', icon: Server },
    { id: 'settings', label: 'Settings', icon: Settings },
    { id: 'account', label: 'Account', icon: Shield },
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
    if (response.status === 204) return undefined as T
    const text = await response.text()
    if (!text) return undefined as T
    return JSON.parse(text) as T
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
        mediaFiles.value = (await api<{ data: MediaItem[] }>('/media')).data
        templates.value = await api<TemplateRow[]>('/templates')

        const design = await api<{
            branding?: { logo?: string | null }
            chrome?: Partial<ChromeConfig>
            buttons?: { style?: string }
        }>('/design')
        siteChrome.value = {
            headerStyle: design.chrome?.headerStyle || 'classic',
            footerStyle: design.chrome?.footerStyle || 'branded',
            buttonStyle: design.buttons?.style || design.chrome?.buttonStyle || 'solid',
            footerShowLogo: design.chrome?.footerShowLogo ?? true,
            footerShowSiteName: design.chrome?.footerShowSiteName ?? true,
            footerTagline: design.chrome?.footerTagline || '',
            footerShowCredit: design.chrome?.footerShowCredit ?? true,
            footerCreditText: design.chrome?.footerCreditText || 'Powered by DiamondCMS',
            footerCreditUrl: design.chrome?.footerCreditUrl || '',
            footerSocials: design.chrome?.footerSocials || [],
            footerSocialStyle: design.chrome?.footerSocialStyle || 'icons',
        }
        if (design.branding?.logo) siteLogo.value = design.branding.logo

        const settingsRows = await api<Array<{ key: string, value: string }>>('/settings').catch(() => [])
        const siteNameRow = settingsRows.find((row) => row.key === 'site_name')
        if (siteNameRow?.value) {
            try {
                const parsed = JSON.parse(siteNameRow.value)
                if (typeof parsed === 'string' && parsed) siteName.value = parsed
            } catch {
                siteName.value = siteNameRow.value
            }
        }

        const menus = await api<Array<{ location: string, items?: Array<{ label?: string, url?: string, children?: unknown[] }> }>>('/menus').catch(() => [])
        const flatten = (items: Array<{ label?: string, url?: string, children?: unknown[] }> = []): ChromeMenuItem[] =>
            items.flatMap((item) => {
                const row = item.label && item.url ? [{ label: item.label, url: item.url }] : []
                const kids = Array.isArray(item.children) ? flatten(item.children as typeof items) : []
                return [...row, ...kids]
            })
        headerMenu.value = flatten(menus.find((m) => m.location === 'header')?.items)
        footerMenu.value = flatten(menus.find((m) => m.location === 'footer')?.items)
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

async function useTemplate(template: TemplateRow, event?: Event): Promise<void> {
    newPageTitle.value = template.name
    showNewPage.value = false
    await createPage(template, event)
}

async function createPage(fromTemplate?: TemplateRow, event?: Event): Promise<void> {
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
        showActionToast(event, 'Page created — start editing')
        await refreshCounts()
    }
    catch (error) {
        showActionToast(event, error instanceof Error ? error.message : 'Could not create page', 'error')
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
    const payload = await api<Dashboard>('/dashboard')
    dashboard.value = {
        ...payload,
        analytics: payload.analytics ?? emptyAnalytics(),
    }
}

async function setPageStatus(page: Page, status: 'draft' | 'published', event?: Event): Promise<void> {
    pageActionBusy.value = page.id
    try {
        const updated = await api<Page>(`/pages/${page.id}`, {
            method: 'PUT',
            body: JSON.stringify({ status }),
        })
        pages.value = pages.value.map((candidate) => (candidate.id === updated.id ? updated : candidate))
        if (selectedPage.value?.id === updated.id) {
            selectedPage.value = updated
        }
        showActionToast(event, status === 'published' ? 'Page published' : 'Page unpublished')
        await refreshCounts()
    }
    catch (error) {
        showActionToast(event, error instanceof Error ? error.message : 'Could not update page', 'error')
    }
    finally {
        pageActionBusy.value = null
    }
}

async function deletePage(page: Page, event?: Event): Promise<void> {
    if (!confirm(`Delete “${page.title}”? It will be archived and removed from the public site.`)) {
        return
    }
    pageActionBusy.value = page.id
    try {
        await api(`/pages/${page.id}`, { method: 'DELETE' })
        pages.value = pages.value.filter((candidate) => candidate.id !== page.id)
        if (selectedPage.value?.id === page.id) {
            closeEditor()
        }
        showActionToast(event, 'Moved to trash')
        await refreshCounts()
    }
    catch (error) {
        showActionToast(event, error instanceof Error ? error.message : 'Could not delete page', 'error')
    }
    finally {
        pageActionBusy.value = null
    }
}

async function onTemplatesSeeded(): Promise<void> {
    templates.value = await api<TemplateRow[]>('/templates')
}

function openNewPageDialog(): void {
    newPageTitle.value = ''
    showNewPage.value = true
}

function openMediaPicker(target: { field: 'src' } | { field: 'images', index: number } = { field: 'src' }): void {
    mediaPickerTarget.value = target
    showMediaPicker.value = true
}

function pickMedia(item: MediaItem): void {
    const target = mediaPickerTarget.value || { field: 'src' as const }
    const url = item.url || `/storage/${item.path}`

    if (target.field === 'images' && selectedBlock.value?.type === 'gallery-grid') {
        snapshot()
        const images = Array.isArray(selectedBlock.value.props.images)
            ? [...selectedBlock.value.props.images as Array<Record<string, unknown>>]
            : []
        while (images.length <= target.index) {
            images.push({ src: '', alt: '' })
        }
        images[target.index] = {
            ...images[target.index],
            src: url,
            alt: (images[target.index]?.alt as string) || item.alt_text || item.original_name,
        }
        selectedBlock.value.props.images = images
    } else if (selectedBlock.value) {
        snapshot()
        selectedBlock.value.props.src = url
        if (!selectedBlock.value.props.alt) {
            selectedBlock.value.props.alt = item.alt_text || item.original_name
        }
    }

    showMediaPicker.value = false
    mediaPickerTarget.value = null
    toast.success('Image selected')
}

async function onImageDropFiles(block: BuilderBlock, files: File[]): Promise<void> {
    const file = files[0]
    if (!file) return
    selectedBlock.value = block
    uploadingImage.value = true
    try {
        snapshot()
        const uploaded = await uploadMediaFile(csrf, file)
        block.props.src = uploaded.url
        if (!block.props.alt) {
            block.props.alt = uploaded.alt_text || uploaded.original_name
        }
        mediaFiles.value = (await api<{ data: MediaItem[] }>('/media')).data
        toast.success('Image uploaded')
    } catch (error) {
        toast.error(error instanceof Error ? error.message : 'Upload failed')
    } finally {
        uploadingImage.value = false
    }
}

async function onTrashChanged(): Promise<void> {
    pages.value = (await api<{ data: Page[] }>('/pages')).data
    mediaFiles.value = (await api<{ data: MediaItem[] }>('/media')).data
    await refreshCounts()
}

watch(activePanel, (panel) => {
    if (panel !== 'pages') mode.value = 'browse'
})

onMounted(load)
</script>

<template>
    <div class="bg-background text-foreground flex min-h-screen">
        <Teleport to="body">
            <Toaster rich-colors position="bottom-right" close-button class="dc-admin-toaster" />
        </Teleport>
        <ActionToastHost />

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
                <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <Card v-for="stat in [
                        { label: 'Views today', value: dashboard.analytics?.page_views_today ?? 0 },
                        { label: 'Views (7 days)', value: dashboard.analytics?.page_views_7d ?? 0 },
                        { label: 'Unique visitors (7d)', value: dashboard.analytics?.unique_visitors_7d ?? 0 },
                        { label: 'Resume downloads', value: dashboard.analytics?.resume_downloads ?? 0 },
                    ]" :key="stat.label">
                        <CardHeader class="pb-2">
                            <CardDescription>{{ stat.label }}</CardDescription>
                            <CardTitle class="text-3xl">{{ stat.value }}</CardTitle>
                        </CardHeader>
                    </Card>
                </div>
                <div class="grid gap-4 lg:grid-cols-2">
                    <Card>
                        <CardHeader>
                            <CardTitle>Most visited pages</CardTitle>
                            <CardDescription>Last 30 days · local analytics only</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <ul v-if="(dashboard.analytics?.top_pages?.length ?? 0) > 0" class="space-y-2 text-sm">
                                <li
                                    v-for="row in dashboard.analytics?.top_pages"
                                    :key="`${row.page_id}-${row.path}`"
                                    class="flex items-center justify-between gap-3 border-b py-2 last:border-0"
                                >
                                    <span class="min-w-0 truncate">
                                        <span class="font-medium">{{ row.title }}</span>
                                        <span class="text-muted-foreground ml-2">{{ row.path }}</span>
                                    </span>
                                    <span class="tabular-nums font-medium">{{ row.visits }}</span>
                                </li>
                            </ul>
                            <p v-else class="text-muted-foreground text-sm">No page visits recorded yet. Open your live site to start collecting local analytics.</p>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader>
                            <CardTitle>Views this week</CardTitle>
                            <CardDescription>{{ dashboard.analytics?.resume_downloads_7d ?? 0 }} résumé downloads in the last 7 days</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <ul v-if="(dashboard.analytics?.daily_views?.length ?? 0) > 0" class="space-y-2 text-sm">
                                <li
                                    v-for="row in dashboard.analytics?.daily_views"
                                    :key="row.day"
                                    class="flex items-center justify-between gap-3"
                                >
                                    <span class="text-muted-foreground">{{ row.day }}</span>
                                    <div class="flex items-center gap-2">
                                        <span
                                            class="bg-primary/80 h-2 rounded-sm"
                                            :style="{ width: `${Math.max(8, Math.round((row.visits / Math.max(...(dashboard.analytics?.daily_views.map((d) => d.visits) || [1]))) * 120))}px` }"
                                        />
                                        <span class="w-8 text-right tabular-nums">{{ row.visits }}</span>
                                    </div>
                                </li>
                            </ul>
                            <p v-else class="text-muted-foreground text-sm">Daily view chart will appear after the first visits.</p>
                        </CardContent>
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
                        <Button variant="outline" class="gap-2" @click="activePanel = 'templates'">
                            <LayoutTemplate class="size-4" />
                            <span>Browse templates</span>
                        </Button>
                        <Button variant="outline" class="gap-2" @click="activePanel = 'settings'">
                            <Settings class="size-4" />
                            <span>Settings</span>
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
                                <TableHead class="w-[280px]" />
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
                                    <div class="flex flex-wrap justify-end gap-1.5">
                                        <Button size="sm" variant="outline" class="gap-1.5" @click="openEditor(page)">
                                            <Pencil class="size-3.5" />
                                            <span>Edit</span>
                                        </Button>
                                        <Button
                                            v-if="page.status === 'published'"
                                            size="sm"
                                            variant="outline"
                                            :disabled="pageActionBusy === page.id"
                                            @click="setPageStatus(page, 'draft', $event)"
                                        >
                                            Unpublish
                                        </Button>
                                        <Button
                                            v-else
                                            size="sm"
                                            variant="outline"
                                            :disabled="pageActionBusy === page.id"
                                            @click="setPageStatus(page, 'published', $event)"
                                        >
                                            Publish
                                        </Button>
                                        <Button
                                            size="sm"
                                            variant="ghost"
                                            class="text-destructive hover:text-destructive gap-1.5"
                                            :disabled="pageActionBusy === page.id"
                                            @click="deletePage(page, $event)"
                                        >
                                            <Trash2 class="size-3.5" />
                                            <span>Delete</span>
                                        </Button>
                                    </div>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </Card>
            </section>

            <MediaPanel
                v-else-if="activePanel === 'media'"
                :items="mediaFiles"
                :csrf="csrf"
                :api="api"
                @refreshed="mediaFiles = $event; refreshCounts()"
            />

            <TrashPanel
                v-else-if="activePanel === 'trash'"
                :api="api"
                @changed="onTrashChanged"
            />

            <TemplatesPanel
                v-else-if="activePanel === 'templates'"
                :templates="templates"
                :api="api"
                @seeded="onTemplatesSeeded"
                @use="(template, event) => useTemplate(template, event)"
            />

            <ThemePanel
                v-else-if="activePanel === 'theme'"
                :api="api"
            />

            <MenusPanel
                v-else-if="activePanel === 'menus'"
                :api="api"
                :pages="pages"
            />

            <FormsPanel
                v-else-if="activePanel === 'forms'"
                :api="api"
            />

            <SettingsPanel
                v-else-if="activePanel === 'settings'"
                :api="api"
                :pages="pages"
            />

            <ResumesPanel
                v-else-if="activePanel === 'resumes'"
                :api="api"
            />

            <PortfolioPanel
                v-else-if="activePanel === 'portfolio'"
                :api="api"
                :csrf="csrf"
            />

            <SeoPanel
                v-else-if="activePanel === 'seo'"
                :api="api"
                :pages="pages"
                @refreshed="load"
            />

            <AiPanel
                v-else-if="activePanel === 'ai'"
                :api="api"
                @refreshed="load"
            />

            <SystemPanel
                v-else-if="activePanel === 'system'"
                :api="api"
            />

            <AccountPanel
                v-else-if="activePanel === 'account'"
                :api="api"
            />
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
                    <Button
                        v-if="selectedPage?.status === 'published'"
                        size="sm"
                        variant="outline"
                        class="gap-1.5"
                        :disabled="saving || pageActionBusy === selectedPage?.id"
                        @click="selectedPage && setPageStatus(selectedPage, 'draft', $event)"
                    >
                        Unpublish
                    </Button>
                    <Button
                        v-else
                        size="sm"
                        class="gap-1.5"
                        :disabled="saving"
                        @click="savePage('published')"
                    >
                        <Upload class="size-4" />
                        <span>Publish</span>
                    </Button>
                    <Button
                        size="sm"
                        variant="ghost"
                        class="text-destructive hover:text-destructive gap-1.5"
                        :disabled="!selectedPage || pageActionBusy === selectedPage?.id"
                        @click="selectedPage && deletePage(selectedPage, $event)"
                    >
                        <Trash2 class="size-4" />
                        <span>Delete</span>
                    </Button>
                    <Button size="sm" variant="outline" class="gap-1.5" @click="openPreview">
                        <ExternalLink class="size-4" />
                        <span>Preview</span>
                    </Button>
                    <Button size="sm" variant="secondary" class="gap-1.5" as-child>
                        <a :href="`/admin/live/${selectedPage?.id}`">
                            <Pencil class="size-4" />
                            <span>Edit live</span>
                        </a>
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

                <section class="min-w-0 overflow-auto p-0 md:p-4" style="background: var(--dc-site-bg, var(--dc-bg)); color: var(--dc-fg)">
                    <div
                        class="mx-auto min-h-[75vh] max-w-6xl overflow-hidden rounded-2xl border shadow-sm"
                        :class="`dc-btn-${siteChrome.buttonStyle}`"
                        :data-dc-button="siteChrome.buttonStyle"
                        style="background: var(--dc-site-bg, var(--dc-bg)); color: var(--dc-fg); border-color: var(--dc-line)"
                    >
                        <PublicSiteChrome
                            :shell="pageShell"
                            :site-name="siteName"
                            :logo-url="siteLogo"
                            :menu-items="headerMenu"
                            :footer-items="footerMenu"
                            :chrome="siteChrome"
                            :public-url="selectedPage ? `/${selectedPage.slug}` : '/'"
                        >
                            <VueDraggable
                                v-model="documentState.blocks"
                                class="dc-live-blocks space-y-1 p-2 md:p-4"
                                group="builder-blocks"
                                :animation="180"
                                @end="snapshot"
                            >
                                <BuilderBlockView
                                    v-for="block in documentState.blocks"
                                    :key="block.id"
                                    :block="block"
                                    :selected-id="selectedBlock?.id ?? null"
                                    :live-mode="true"
                                    @select="selectedBlock = $event"
                                    @update="snapshot"
                                    @remove="deleteBlock"
                                    @add-child="addChildTo"
                                    @drop-files="onImageDropFiles"
                                    @pick-media="(block) => { selectedBlock = block; openMediaPicker({ field: 'src' }) }"
                                />
                            </VueDraggable>
                            <div v-if="documentState.blocks.length === 0" class="p-12 text-center text-sm" style="color: var(--dc-muted)">
                                Add a section kit from the left to start designing this page.
                            </div>
                        </PublicSiteChrome>
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
                            <p v-if="uploadingImage" class="text-muted-foreground text-xs">Uploading image…</p>
                            <BlockPropsEditor
                                :block="selectedBlock"
                                @change="snapshot"
                                @pick-media="openMediaPicker"
                            />
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

        <!-- Media picker modal -->
        <div
            v-if="showMediaPicker"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
            @click.self="showMediaPicker = false"
        >
            <Card class="flex max-h-[85vh] w-full max-w-4xl flex-col overflow-hidden shadow-xl">
                <CardHeader class="flex-row items-center justify-between space-y-0">
                    <CardTitle>Choose image</CardTitle>
                    <Button variant="ghost" size="icon-sm" @click="showMediaPicker = false">
                        <X class="size-4" />
                    </Button>
                </CardHeader>
                <CardContent class="overflow-auto">
                    <MediaPanel
                        :items="mediaFiles"
                        :csrf="csrf"
                        :api="api"
                        selectable
                        @refreshed="mediaFiles = $event"
                        @select="pickMedia"
                    />
                </CardContent>
            </Card>
        </div>
    </div>
</template>
