<script setup lang="ts">
import { computed, ref } from 'vue'
import { VueDraggable } from 'vue-draggable-plus'
import { GripVertical, Plus, Trash2 } from '@lucide/vue'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { cn } from '@/lib/utils'
import BuilderBlockView from './BuilderBlockView.vue'
import SocialBrandIcon from './SocialBrandIcon.vue'

export type BuilderBlock = {
    id: string
    type: string
    props: Record<string, unknown>
    children?: BuilderBlock[]
}

const props = defineProps<{
    block: BuilderBlock
    selectedId: string | null
    depth?: number
    readonly?: boolean
    previewMode?: boolean
    liveMode?: boolean
}>()

const emit = defineEmits<{
    select: [block: BuilderBlock]
    update: []
    remove: [block: BuilderBlock]
    addChild: [parent: BuilderBlock, type: string]
    dropFiles: [block: BuilderBlock, files: File[]]
    pickMedia: [block: BuilderBlock]
}>()

const depth = computed(() => props.depth ?? 0)
const isSelected = computed(() => props.selectedId === props.block.id)
const interactive = computed(() => !props.readonly && !props.previewMode)
const live = computed(() => !!props.liveMode)
const isContainer = computed(() => props.block.type === 'section' || props.block.type === 'columns')
const isInlineLeaf = computed(() => live.value && ['button'].includes(props.block.type))

function wrapperClass(): string {
    if (!live.value) {
        return cn(
            'group/block relative rounded-lg transition',
            interactive.value && (isSelected.value ? 'ring-2 ring-primary ring-offset-2' : 'hover:ring-1 hover:ring-foreground/15'),
            depth.value === 0 ? 'mb-3' : 'mb-2',
            props.previewMode && 'pointer-events-none',
        )
    }

    if (isContainer.value) {
        return cn(
            'dc-live-leaf--block group/block relative min-w-0',
            interactive.value && isSelected.value && 'dc-live-selected',
        )
    }

    if (isInlineLeaf.value) {
        return cn(
            'dc-live-leaf--inline group/block relative',
            interactive.value && isSelected.value && 'dc-live-selected',
        )
    }

    // display:contents so headings/text/stats flow like public SSR siblings
    return 'dc-live-leaf group/block'
}

function liveSelectedClass(): string {
    return live.value && isSelected.value && !isContainer.value && !isInlineLeaf.value
        ? 'dc-live-selected'
        : ''
}

function selectSelf(event: Event): void {
    if (!interactive.value) return
    event.stopPropagation()
    emit('select', props.block)
}

function onInline(key: string, event: Event): void {
    if (!interactive.value) return
    const target = event.target as HTMLElement
    props.block.props[key] = target.innerText
    emit('update')
}

function ensureStatsItems(): Array<Record<string, unknown>> {
    if (!Array.isArray(props.block.props.items) || props.block.props.items.length === 0) {
        props.block.props.items = statsItems.value.map((row) => ({ ...row }))
    }
    return props.block.props.items as Array<Record<string, unknown>>
}

function onStatField(index: number, key: 'value' | 'label', event: Event): void {
    if (!interactive.value) return
    const items = ensureStatsItems()
    if (!items[index]) return
    items[index][key] = (event.target as HTMLElement).innerText.trim()
    emit('update')
}

const imageDragOver = ref(false)

function onImageDragOver(event: DragEvent): void {
    if (!interactive.value) return
    if (![...(event.dataTransfer?.types || [])].includes('Files')) return
    event.preventDefault()
    event.stopPropagation()
    imageDragOver.value = true
}

function onImageDragLeave(event: DragEvent): void {
    event.preventDefault()
    imageDragOver.value = false
}

function onImageDrop(event: DragEvent): void {
    if (!interactive.value) return
    event.preventDefault()
    event.stopPropagation()
    imageDragOver.value = false
    const files = Array.from(event.dataTransfer?.files || []).filter((file) => file.type.startsWith('image/'))
    if (!files.length) return
    emit('select', props.block)
    emit('dropFiles', props.block, files)
}

function previewPadding(raw: unknown): string {
    if (props.previewMode) {
        // Compact padding so template thumbnails aren't empty space.
        const value = String(raw || '1rem')
        return value
            .replace(/(\d+(?:\.\d+)?)rem/g, (_, n) => `${Math.min(Number(n), 1.1)}rem`)
            .replace(/(\d+(?:\.\d+)?)px/g, (_, n) => `${Math.min(Number(n), 16)}px`)
    }
    if (props.liveMode && (props.depth ?? 0) > 0) {
        return String(raw || '0')
    }
    return String(raw || '3rem 1.25rem')
}

function headingTag(): string {
    const level = Math.min(6, Math.max(1, Number(props.block.props.level || 2)))
    return `h${level}`
}

function asItems(value: unknown): Array<Record<string, unknown>> {
    if (!Array.isArray(value)) return []
    return value.filter((row): row is Record<string, unknown> => !!row && typeof row === 'object')
}

const statsItems = computed(() => {
    const items = asItems(props.block.props.items)
    if (items.length) return items
    return [
        { value: '10+', label: 'Years' },
        { value: '50+', label: 'Projects' },
        { value: '98%', label: 'Occupancy' },
    ]
})

const socialItems = computed(() => {
    const items = asItems(props.block.props.items)
    if (items.length) return items
    return [
        { label: 'Email', url: 'mailto:hello@example.com', icon: 'email' },
        { label: 'LinkedIn', url: '#', icon: 'linkedin' },
        { label: 'Instagram', url: '#', icon: 'instagram' },
    ]
})

const socialVariant = computed(() => String(props.block.props.variant || 'icons-labels'))

const timelineItems = computed(() => {
    const items = asItems(props.block.props.items)
    if (items.length) return items
    return [
        { date: '2019 — Current', title: 'Property Manager', organization: 'Woodmont', bullets: ['Led multi-site operations.'] },
        { date: '2017 — 2019', title: 'Assistant Manager', organization: 'Greystar', bullets: ['Leasing and resident care.'] },
    ]
})

const galleryImages = computed(() => {
    const images = asItems(props.block.props.images)
    if (images.length) return images
    return [
        { src: '/brand/logo-primary-gold.svg', alt: '1' },
        { src: '/brand/logo-gold-on-charcoal.svg', alt: '2' },
        { src: '/brand/diamond-icon-gold.svg', alt: '3' },
    ]
})
</script>

<template>
    <div
        :class="wrapperClass()"
        @click="selectSelf"
    >
        <div
            v-if="interactive && !live"
            class="absolute -top-3 left-3 z-10 flex items-center gap-1 opacity-0 transition group-hover/block:opacity-100"
            :class="{ 'opacity-100': isSelected }"
        >
            <Badge variant="secondary" class="gap-1 text-[10px] uppercase">
                <GripVertical class="size-3" />
                {{ block.type }}
            </Badge>
            <Button
                size="icon-sm"
                variant="destructive"
                class="opacity-90"
                @click.stop="emit('remove', block)"
            >
                <Trash2 class="size-3" />
            </Button>
        </div>

        <div
            v-if="interactive && live && (isContainer || isInlineLeaf)"
            class="dc-live-chrome"
            @click.stop
        >
            <Badge variant="secondary" class="gap-1 text-[10px] uppercase">
                <GripVertical class="size-3" />
                {{ block.type }}
            </Badge>
            <Button
                size="icon-sm"
                variant="destructive"
                class="opacity-90"
                @click.stop="emit('remove', block)"
            >
                <Trash2 class="size-3" />
            </Button>
        </div>

        <section
            v-if="block.type === 'section'"
            :class="cn(
                live
                    ? (depth === 0 ? 'dc-section dc-live-section min-h-16' : 'dc-section dc-live-section dc-section--nested min-h-0')
                    : cn('min-h-16 rounded-xl border border-dashed p-4 md:p-6', previewMode ? 'border-transparent bg-transparent' : 'bg-background'),
            )"
            :style="{ padding: previewPadding(block.props.padding) }"
        >
            <VueDraggable
                v-if="interactive"
                v-model="block.children!"
                :class="live ? 'dc-live-stack' : 'min-h-12 space-y-3'"
                group="builder-blocks"
                :animation="180"
                @end="emit('update')"
            >
                <BuilderBlockView
                    v-for="child in block.children"
                    :key="child.id"
                    :block="child"
                    :selected-id="selectedId"
                    :depth="depth + 1"
                    :readonly="readonly"
                    :preview-mode="previewMode"
                    :live-mode="liveMode"
                    @select="emit('select', $event)"
                    @update="emit('update')"
                    @remove="emit('remove', $event)"
                    @add-child="(parent, type) => emit('addChild', parent, type)"
                    @drop-files="(block, files) => emit('dropFiles', block, files)"
                    @pick-media="emit('pickMedia', $event)"
                />
            </VueDraggable>
            <div v-else :class="live ? 'dc-live-stack' : 'space-y-3'">
                <BuilderBlockView
                    v-for="child in block.children"
                    :key="child.id"
                    :block="child"
                    :selected-id="null"
                    :depth="depth + 1"
                    :readonly="true"
                    :preview-mode="previewMode"
                    :live-mode="liveMode"
                />
            </div>
            <div v-if="interactive && !block.children?.length" class="text-muted-foreground py-8 text-center text-sm">
                Drop blocks here or add from the left panel.
            </div>
            <div
                v-if="interactive && (!live || isSelected)"
                class="mt-3 flex flex-wrap gap-1.5"
                :class="live ? '' : 'opacity-0 transition group-hover/block:opacity-100'"
                :style="!live && isSelected ? undefined : undefined"
            >
                <template v-if="!live || isSelected">
                    <Button size="sm" variant="outline" class="gap-1" @click.stop="emit('addChild', block, 'heading')">
                        <Plus class="size-3" /> Heading
                    </Button>
                    <Button size="sm" variant="outline" class="gap-1" @click.stop="emit('addChild', block, 'text')">
                        <Plus class="size-3" /> Text
                    </Button>
                    <Button size="sm" variant="outline" class="gap-1" @click.stop="emit('addChild', block, 'button')">
                        <Plus class="size-3" /> Button
                    </Button>
                    <Button size="sm" variant="outline" class="gap-1" @click.stop="emit('addChild', block, 'image')">
                        <Plus class="size-3" /> Image
                    </Button>
                    <Button size="sm" variant="outline" class="gap-1" @click.stop="emit('addChild', block, 'columns')">
                        <Plus class="size-3" /> Columns
                    </Button>
                    <Button size="sm" variant="outline" class="gap-1" @click.stop="emit('addChild', block, 'stats-row')">
                        <Plus class="size-3" /> Stats
                    </Button>
                    <Button size="sm" variant="outline" class="gap-1" @click.stop="emit('addChild', block, 'timeline')">
                        <Plus class="size-3" /> Timeline
                    </Button>
                    <Button size="sm" variant="outline" class="gap-1" @click.stop="emit('addChild', block, 'form')">
                        <Plus class="size-3" /> Form
                    </Button>
                    <Button size="sm" variant="outline" class="gap-1" @click.stop="emit('addChild', block, 'portfolio-featured-grid')">
                        <Plus class="size-3" /> Portfolio
                    </Button>
                </template>
            </div>
        </section>

        <VueDraggable
            v-else-if="block.type === 'columns' && interactive"
            v-model="block.children!"
            :class="live ? 'dc-columns' : 'grid gap-4 rounded-xl border border-dashed p-3'"
            :style="live
                ? { '--dc-columns': Math.max(1, Number(block.props.columns || 2)) }
                : { gridTemplateColumns: `repeat(${Math.max(1, Number(block.props.columns || 2))}, minmax(0, 1fr))` }"
            :data-dc-mobile-stack="live ? String(block.props.mobileStack || 'auto') : undefined"
            group="builder-blocks"
            :animation="180"
            @end="emit('update')"
        >
            <BuilderBlockView
                v-for="child in block.children"
                :key="child.id"
                :block="child"
                :selected-id="selectedId"
                :depth="depth + 1"
                :readonly="readonly"
                :preview-mode="previewMode"
                :live-mode="liveMode"
                @select="emit('select', $event)"
                @update="emit('update')"
                @remove="emit('remove', $event)"
                @add-child="(parent, type) => emit('addChild', parent, type)"
                @drop-files="(block, files) => emit('dropFiles', block, files)"
                @pick-media="emit('pickMedia', $event)"
            />
        </VueDraggable>

        <div
            v-else-if="block.type === 'columns'"
            :class="live ? 'dc-columns' : cn('grid gap-4 rounded-xl border border-dashed p-3', previewMode && 'border-transparent')"
            :style="live
                ? { '--dc-columns': Math.max(1, Number(block.props.columns || 2)) }
                : { gridTemplateColumns: `repeat(${Math.max(1, Number(block.props.columns || 2))}, minmax(0, 1fr))` }"
            :data-dc-mobile-stack="live ? String(block.props.mobileStack || 'auto') : undefined"
            >
            <BuilderBlockView
                v-for="child in block.children"
                :key="child.id"
                :block="child"
                :selected-id="null"
                :depth="depth + 1"
                :readonly="true"
                :preview-mode="previewMode"
                :live-mode="liveMode"
            />
        </div>

        <component
            :is="headingTag()"
            v-else-if="block.type === 'heading'"
            :class="cn(live ? 'dc-heading outline-none' : 'font-semibold outline-none', liveSelectedClass())"
            :contenteditable="interactive"
            @blur="onInline('text', $event)"
            @click.stop="selectSelf"
        >{{ block.props.text }}</component>

        <div
            v-else-if="block.type === 'text'"
            :class="cn(live ? 'dc-text outline-none' : 'leading-relaxed whitespace-pre-wrap opacity-80 outline-none', liveSelectedClass())"
            :contenteditable="interactive"
            @blur="onInline('text', $event)"
            @click.stop="selectSelf"
        >{{ block.props.text }}</div>

        <span
            v-else-if="block.type === 'button'"
            :class="cn('dc-button outline-none', liveSelectedClass())"
            :contenteditable="interactive"
            @blur="onInline('text', $event)"
            @click.stop="selectSelf"
        >{{ block.props.text }}</span>

        <div
            v-else-if="block.type === 'image'"
            :class="cn(
                live ? 'relative' : 'relative overflow-hidden rounded-xl border',
                liveSelectedClass(),
                interactive && imageDragOver ? 'ring-primary ring-2 ring-offset-2' : '',
            )"
            @click.stop="selectSelf"
            @dragover="onImageDragOver"
            @dragleave="onImageDragLeave"
            @drop="onImageDrop"
        >
            <img
                v-if="block.props.src"
                :src="String(block.props.src)"
                :alt="String(block.props.alt || '')"
                :class="live ? 'dc-image pointer-events-none' : 'pointer-events-none h-auto max-h-80 w-full object-cover'"
            >
            <div
                v-else
                class="flex h-40 flex-col items-center justify-center gap-2 bg-black/20 px-4 text-center text-sm opacity-80"
            >
                <span>Drop a photo here</span>
                <span class="text-xs opacity-70">or click and pick from media</span>
            </div>
            <div
                v-if="interactive"
                class="absolute inset-x-2 bottom-2 flex flex-wrap justify-center gap-1.5"
                :class="live ? 'opacity-100' : 'opacity-0 transition group-hover/block:opacity-100'"
            >
                <Button size="sm" variant="secondary" class="gap-1 shadow" @click.stop="emit('pickMedia', block)">
                    Library
                </Button>
                <Button size="sm" variant="secondary" class="shadow" @click.stop="selectSelf">
                    {{ block.props.src ? 'Replace' : 'Select' }}
                </Button>
            </div>
            <div
                v-if="interactive && imageDragOver"
                class="bg-primary/20 text-primary pointer-events-none absolute inset-0 flex items-center justify-center text-sm font-semibold backdrop-blur-[1px]"
            >
                Drop to upload
            </div>
        </div>

        <div
            v-else-if="block.type === 'spacer'"
            class="rounded border border-dashed opacity-40"
            :style="{ height: String(block.props.height || '2rem') }"
        />

        <hr v-else-if="block.type === 'divider'" class="my-2 opacity-30">

        <div
            v-else-if="block.type === 'stats-row'"
            :class="cn(live ? 'dc-stats-row' : 'grid grid-cols-3 gap-3 py-2', liveSelectedClass())"
            @click.stop="selectSelf"
        >
            <div v-for="(item, index) in statsItems" :key="index" :class="live ? 'dc-stat' : 'text-center'">
                <div
                    :class="cn(live ? 'dc-stat-value' : 'text-xl font-bold tracking-tight', interactive ? 'outline-none' : '')"
                    :contenteditable="interactive"
                    @blur="onStatField(index, 'value', $event)"
                    @click.stop="selectSelf"
                >{{ item.value }}</div>
                <div
                    :class="cn(live ? 'dc-stat-label' : 'text-[11px] uppercase tracking-wide opacity-60', interactive ? 'outline-none' : '')"
                    :contenteditable="interactive"
                    @blur="onStatField(index, 'label', $event)"
                    @click.stop="selectSelf"
                >{{ item.label }}</div>
            </div>
        </div>

        <div
            v-else-if="block.type === 'social-links'"
            :class="cn(
                live ? `dc-social-links dc-social-links--${socialVariant}` : 'flex flex-wrap gap-2 py-1',
                liveSelectedClass(),
            )"
        >
            <a
                v-for="(item, index) in socialItems"
                :key="index"
                :class="live
                    ? cn(
                        'dc-social-link',
                        socialVariant === 'icons' && 'dc-social-link--icon',
                        socialVariant === 'pills' && 'dc-social-link--pill',
                        socialVariant === 'list' && 'dc-social-link--list',
                        socialVariant === 'icons-labels' && 'dc-social-link--labeled',
                    )
                    : 'inline-flex items-center gap-2 rounded-md border px-2 py-1 text-sm'"
                :href="String(item.url || '#')"
                :title="String(item.label || '')"
                @click.prevent
            >
                <template v-if="socialVariant === 'list'">
                    <span :class="live ? 'dc-social-dot' : 'inline-block size-2 rounded-full bg-sky-500'" aria-hidden="true" />
                    <span>{{ item.label }}</span>
                </template>
                <template v-else>
                    <SocialBrandIcon
                        :slug="String(item.icon || '')"
                        :label="String(item.label || '')"
                        :url="String(item.url || '')"
                        :size="live && socialVariant === 'icons' ? 20 : 16"
                    />
                    <span v-if="socialVariant !== 'icons'" :class="live ? 'dc-social-label' : ''">{{ item.label }}</span>
                    <span v-else class="sr-only">{{ item.label }}</span>
                </template>
            </a>
        </div>

        <div
            v-else-if="block.type === 'timeline'"
            :class="cn(live ? 'dc-timeline' : 'space-y-3 border-l border-current/20 pl-4', liveSelectedClass())"
        >
            <article v-for="(item, index) in timelineItems" :key="index" :class="live ? 'dc-timeline-item' : 'space-y-1'">
                <span :class="live ? 'dc-timeline-date' : 'inline-block rounded bg-emerald-600/90 px-2 py-0.5 text-[10px] font-semibold text-white'">{{ item.date }}</span>
                <h3 :class="live ? 'dc-timeline-title' : 'text-sm font-semibold'">
                    {{ item.title }}
                    <span v-if="item.organization" :class="live ? 'dc-timeline-org' : 'font-normal opacity-70'"> · {{ item.organization }}</span>
                </h3>
                <ul v-if="Array.isArray(item.bullets)" :class="live ? 'dc-timeline-bullets' : 'list-disc pl-4 text-xs opacity-75'">
                    <li v-for="(bullet, bIndex) in item.bullets" :key="bIndex">{{ bullet }}</li>
                </ul>
            </article>
        </div>

        <div
            v-else-if="block.type === 'gallery-grid'"
            :class="cn(live ? 'dc-gallery-grid' : 'grid grid-cols-3 gap-2', liveSelectedClass())"
        >
            <div
                v-for="(image, index) in galleryImages"
                :key="index"
                :class="live ? 'dc-gallery-item' : 'aspect-square overflow-hidden rounded-lg border bg-black/10'"
            >
                <img v-if="image.src" :src="String(image.src)" :alt="String(image.alt || '')" :class="live ? '' : 'size-full object-cover'">
            </div>
        </div>

        <div
            v-else-if="block.type === 'form'"
            class="space-y-2 rounded-xl border border-dashed p-4"
        >
            <p class="text-[10px] font-semibold tracking-wide uppercase opacity-60">Contact form</p>
            <div class="h-2 w-full rounded bg-current/10" />
            <div class="h-2 w-3/4 rounded bg-current/10" />
            <div class="dc-preview-btn mt-2 inline-flex rounded-md px-3 py-1.5 text-xs font-semibold">Send message</div>
        </div>

        <div
            v-else-if="block.type === 'resume-download'"
            class="py-1"
        >
            <span class="inline-flex rounded-lg border px-4 py-2 text-sm font-semibold">{{ block.props.text || 'Download PDF' }}</span>
        </div>

        <div
            v-else-if="block.type.startsWith('resume')"
            class="space-y-2 rounded-xl border border-dashed p-4"
        >
            <p class="text-[10px] font-semibold tracking-wide uppercase opacity-60">{{ block.type.replace(/-/g, ' ') }}</p>
            <div class="space-y-1">
                <div class="h-2 w-full rounded bg-current/15" />
                <div class="h-2 w-5/6 rounded bg-current/10" />
                <div class="h-2 w-4/6 rounded bg-current/10" />
            </div>
        </div>

        <div
            v-else-if="block.type.startsWith('portfolio')"
            class="grid grid-cols-2 gap-2"
        >
            <div v-for="n in 4" :key="n" class="space-y-1 rounded-lg border p-2">
                <div class="aspect-video rounded bg-gradient-to-br from-current/20 to-current/5" />
                <div class="h-2 w-3/4 rounded bg-current/20" />
                <div class="h-1.5 w-full rounded bg-current/10" />
            </div>
        </div>

        <div v-else class="rounded-lg border border-dashed p-4 text-sm opacity-70">
            {{ block.type }}
        </div>
    </div>
</template>

<script lang="ts">
export default {
    name: 'BuilderBlockView',
}
</script>
