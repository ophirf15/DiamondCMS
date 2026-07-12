<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { VueDraggable } from 'vue-draggable-plus'
import {
    ArrowLeft,
    ExternalLink,
    LayoutTemplate,
    PanelLeft,
    Save,
    Upload,
    X,
} from '@lucide/vue'
import BuilderBlockView, { type BuilderBlock } from '@/components/builder/BuilderBlockView.vue'
import BlockPropsEditor from '@/components/builder/BlockPropsEditor.vue'
import PublicSiteChrome, { type ChromeConfig } from '@/components/builder/PublicSiteChrome.vue'
import MediaPanel, { type MediaItem } from '@/components/admin/MediaPanel.vue'
import { SECTION_KITS } from '@/components/builder/sectionKits'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { toast } from 'vue-sonner'
import { Toaster } from '@/components/ui/sonner'
import { uploadMediaFile } from '@/lib/mediaUpload'
import { initThemeToggle } from '@/public'

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
    builder_json: string | BuilderDocument
}

type MenuItem = { label: string; url: string }

type Boot = {
    page: Page
    siteName: string
    logoUrl: string
    menuItems: MenuItem[]
    footerItems?: MenuItem[]
    adminUrl: string
    publicUrl: string
    chrome?: ChromeConfig
    visitorToggle?: boolean
    themeDefault?: string
    themeLock?: boolean
    shell?: string
}

const root = document.getElementById('live-editor-app')
const boot = JSON.parse(root?.dataset.boot || '{}') as Boot

const page = ref<Page>(boot.page)
const documentState = ref<BuilderDocument>(parseDocument(boot.page))
const selectedBlock = ref<BuilderBlock | null>(null)
const saving = ref(false)
const panelOpen = ref(true)
const mediaFiles = ref<MediaItem[]>([])
const showMediaPicker = ref(false)
const mediaPickerTarget = ref<{ field: 'src' } | { field: 'images', index: number } | null>(null)
const uploadingImage = ref(false)

const shell = computed(() => documentState.value.meta?.shell || boot.shell || 'default')
const siteName = boot.siteName || 'DiamondCMS'
const logoUrl = boot.logoUrl || '/brand/logo-white.svg'
const menuItems = boot.menuItems?.length
    ? boot.menuItems
    : [
        { label: 'Home', url: '/' },
        { label: 'About', url: '/#about' },
        { label: 'Resume', url: '/resume' },
        { label: 'Portfolio', url: '/projects' },
        { label: 'Contact', url: '/contact' },
    ]
const footerItems = boot.footerItems || []
const chrome = computed<ChromeConfig>(() => boot.chrome || {
    headerStyle: 'classic',
    footerStyle: 'branded',
    buttonStyle: 'solid',
    footerShowLogo: true,
    footerShowSiteName: true,
    footerTagline: '',
    footerSocials: [],
    footerSocialStyle: 'icons',
})
const visitorToggle = !!boot.visitorToggle

function parseDocument(row: Page): BuilderDocument {
    const raw = typeof row.builder_json === 'string'
        ? JSON.parse(row.builder_json) as BuilderDocument
        : row.builder_json
    return {
        schema: raw?.schema ?? 1,
        title: raw?.title || row.title,
        meta: raw?.meta || {},
        blocks: raw?.blocks || [],
    }
}

function csrf(): string {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
}

async function api<T>(url: string, options: RequestInit = {}): Promise<T> {
    const response = await fetch(`/admin/api${url}`, {
        ...options,
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrf(),
            'X-Requested-With': 'XMLHttpRequest',
            ...(options.headers || {}),
        },
        credentials: 'same-origin',
    })
    if (!response.ok) {
        const body = await response.json().catch(() => null) as { message?: string } | null
        throw new Error(body?.message || `Request failed (${response.status})`)
    }
    if (response.status === 204) return undefined as T
    return await response.json() as T
}

function onUpdate(): void {
    // reactive document already mutated
}

function selectBlock(block: BuilderBlock): void {
    selectedBlock.value = block
    panelOpen.value = true
}

function removeBlock(list: BuilderBlock[], target: BuilderBlock): boolean {
    const index = list.findIndex((row) => row.id === target.id)
    if (index >= 0) {
        list.splice(index, 1)
        return true
    }
    for (const row of list) {
        if (row.children && removeBlock(row.children, target)) return true
    }
    return false
}

function onRemove(block: BuilderBlock): void {
    removeBlock(documentState.value.blocks, block)
    if (selectedBlock.value?.id === block.id) selectedBlock.value = null
}

function addChild(parent: BuilderBlock, type: string): void {
    if (!parent.children) parent.children = []
    parent.children.push({
        id: crypto.randomUUID(),
        type,
        props: type === 'heading'
            ? { level: 2, text: 'Heading' }
            : type === 'button'
                ? { text: 'Learn more', url: '#' }
                : type === 'text'
                    ? { text: 'Write your copy here.' }
                    : {},
        children: type === 'section' || type === 'columns' ? [] : undefined,
    })
}

function addKit(kitId: string): void {
    const kit = SECTION_KITS.find((row) => row.id === kitId)
    if (!kit) return
    documentState.value.blocks.push(kit.build())
    toast.success(`Added ${kit.label}`)
}

async function save(status?: string): Promise<void> {
    saving.value = true
    try {
        const updated = await api<Page>(`/pages/${page.value.id}`, {
            method: 'PUT',
            body: JSON.stringify({
                title: page.value.title,
                slug: page.value.slug,
                status: status ?? page.value.status,
                builder_json: documentState.value,
            }),
        })
        page.value = updated
        toast.success(status === 'published' ? 'Published — live site updated' : 'Draft saved')
    } catch (error) {
        toast.error(error instanceof Error ? error.message : 'Could not save')
    } finally {
        saving.value = false
    }
}

async function openPublic(): Promise<void> {
    try {
        await save()
        if (page.value.status === 'published') {
            window.open(boot.publicUrl || `/${page.value.slug}`, '_blank', 'noopener')
            return
        }
        const result = await api<{ url: string }>(`/pages/${page.value.id}/preview-token`, {
            method: 'POST',
            body: '{}',
        })
        window.open(result.url, '_blank', 'noopener')
    } catch (error) {
        toast.error(error instanceof Error ? error.message : 'Could not open view')
    }
}

function openMediaPicker(target: { field: 'src' } | { field: 'images', index: number } = { field: 'src' }): void {
    mediaPickerTarget.value = target
    showMediaPicker.value = true
}

function pickMedia(item: MediaItem): void {
    if (!selectedBlock.value) return
    const target = mediaPickerTarget.value || { field: 'src' as const }
    const url = item.url || `/storage/${item.path}`

    if (target.field === 'images' && selectedBlock.value.type === 'gallery-grid') {
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
    } else {
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
    panelOpen.value = true
    uploadingImage.value = true
    try {
        const uploaded = await uploadMediaFile(csrf(), file)
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

async function loadMedia(): Promise<void> {
    try {
        mediaFiles.value = (await api<{ data: MediaItem[] }>('/media')).data
    } catch {
        mediaFiles.value = []
    }
}

onMounted(() => {
    document.body.classList.add('dc-live-editing')
    document.body.classList.add(`dc-btn-${chrome.value.buttonStyle}`)
    document.body.dataset.dcButton = chrome.value.buttonStyle
    void loadMedia()
    queueMicrotask(() => initThemeToggle())
})
</script>

<template>
    <div class="dc-live-root">
        <Toaster position="bottom-right" rich-colors />

        <header class="dc-live-toolbar">
            <div class="dc-live-toolbar-left">
                <Button size="sm" variant="secondary" class="gap-1.5" as-child>
                    <a :href="boot.adminUrl || '/admin/dashboard'">
                        <ArrowLeft class="size-4" />
                        Admin
                    </a>
                </Button>
                <Button size="sm" variant="secondary" class="gap-1.5" @click="panelOpen = !panelOpen">
                    <PanelLeft class="size-4" />
                    {{ panelOpen ? 'Hide kits' : 'Show kits' }}
                </Button>
                <div class="dc-live-title">
                    <strong>{{ page.title }}</strong>
                    <Badge variant="secondary">{{ page.status }}</Badge>
                </div>
            </div>
            <div class="dc-live-toolbar-right">
                <Button size="sm" variant="secondary" class="gap-1.5" :disabled="saving" @click="save()">
                    <Save class="size-4" />
                    Save draft
                </Button>
                <Button size="sm" class="gap-1.5" :disabled="saving" @click="save('published')">
                    <Upload class="size-4" />
                    Publish
                </Button>
                <Button size="sm" variant="secondary" class="gap-1.5" @click="openPublic">
                    <ExternalLink class="size-4" />
                    View site
                </Button>
            </div>
        </header>

        <div class="dc-live-workspace" :class="{ 'dc-live-workspace--panel': panelOpen }">
            <aside v-if="panelOpen" class="dc-live-panel">
                <div class="dc-live-panel-head">
                    <LayoutTemplate class="size-4" />
                    <span>{{ selectedBlock ? 'Block settings' : 'Section kits' }}</span>
                    <button type="button" class="dc-live-panel-close" @click="panelOpen = false">
                        <X class="size-4" />
                    </button>
                </div>
                <template v-if="selectedBlock">
                    <p class="dc-live-panel-hint">Editing {{ selectedBlock.type.replace(/-/g, ' ') }}. Click empty canvas to add kits again.</p>
                    <div class="dc-live-props">
                        <p v-if="uploadingImage" class="text-muted-foreground mb-2 text-xs">Uploading image…</p>
                        <BlockPropsEditor
                            :block="selectedBlock"
                            @change="onUpdate"
                            @pick-media="openMediaPicker"
                        />
                        <Button size="sm" variant="secondary" class="mt-3 w-full" @click="selectedBlock = null">
                            Done
                        </Button>
                    </div>
                </template>
                <template v-else>
                    <p class="dc-live-panel-hint">Add ready-made sections onto the live page.</p>
                    <div class="dc-live-kits">
                        <button
                            v-for="kit in SECTION_KITS"
                            :key="kit.id"
                            type="button"
                            class="dc-live-kit"
                            @click="addKit(kit.id)"
                        >
                            <strong>{{ kit.label }}</strong>
                            <span>{{ kit.description }}</span>
                        </button>
                    </div>
                </template>
            </aside>

            <div class="dc-live-stage">
                <PublicSiteChrome
                    :shell="shell"
                    :site-name="siteName"
                    :logo-url="logoUrl"
                    :menu-items="menuItems"
                    :footer-items="footerItems"
                    :chrome="chrome"
                    :visitor-toggle="visitorToggle"
                    :public-url="boot.publicUrl || '/'"
                >
                    <VueDraggable
                        v-model="documentState.blocks"
                        class="dc-live-blocks"
                        group="builder-blocks"
                        :animation="180"
                        @end="onUpdate"
                    >
                        <BuilderBlockView
                            v-for="block in documentState.blocks"
                            :key="block.id"
                            :block="block"
                            :selected-id="selectedBlock?.id ?? null"
                            :live-mode="true"
                            @select="selectBlock"
                            @update="onUpdate"
                            @remove="onRemove"
                            @add-child="addChild"
                            @drop-files="onImageDropFiles"
                            @pick-media="(block) => { selectBlock(block); openMediaPicker({ field: 'src' }) }"
                        />
                    </VueDraggable>
                    <p v-if="!documentState.blocks.length" class="dc-live-empty">
                        Add a section kit from the left to start building.
                    </p>
                </PublicSiteChrome>
            </div>
        </div>

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
                    :csrf="csrf()"
                    :api="api"
                    selectable
                    @refreshed="mediaFiles = $event"
                    @select="pickMedia"
                />
            </div>
        </div>
    </div>
</template>
