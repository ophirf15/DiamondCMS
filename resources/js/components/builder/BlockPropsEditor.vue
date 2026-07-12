<script setup lang="ts">
import { computed, ref } from 'vue'
import { Plus, Trash2 } from '@lucide/vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import IconPicker from '@/components/ui/IconPicker.vue'
import SocialBrandIcon from '@/components/builder/SocialBrandIcon.vue'
import type { BuilderBlock } from '@/components/builder/BuilderBlockView.vue'

const props = defineProps<{
    block: BuilderBlock
}>()

const emit = defineEmits<{
    change: []
    pickMedia: [target: { field: 'src' } | { field: 'images', index: number }]
}>()

type StatItem = { value: string, label: string }
type SocialItem = { label: string, url: string, icon: string }
type TimelineItem = { date: string, title: string, organization: string, bullets: string[] }
type GalleryItem = { src: string, alt: string }

const iconPickerOpen = ref(false)
const iconPickerIndex = ref(0)

const socialVariants = [
    { key: 'list', label: 'Text list' },
    { key: 'icons', label: 'Icons only' },
    { key: 'icons-labels', label: 'Icons + labels' },
    { key: 'pills', label: 'Pills' },
]

function touch(): void {
    emit('change')
}

function isPlain(value: unknown): boolean {
    return value === null || ['string', 'number', 'boolean'].includes(typeof value)
}

const scalarKeys = computed(() =>
    Object.keys(props.block.props).filter((key) => {
        if (key === 'items' || key === 'images' || key === 'bullets' || key === 'variant') return false
        // Image src is handled with media picker UI below.
        if (props.block.type === 'image' && key === 'src') return false
        return isPlain(props.block.props[key])
    }),
)

const isMultiline = (key: string): boolean => key === 'text' || key === 'html' || key === 'excerpt' || key === 'summary'

const socialVariant = computed({
    get(): string {
        return String(props.block.props.variant || 'icons-labels')
    },
    set(value: string) {
        props.block.props.variant = value
        touch()
    },
})

const statsItems = computed({
    get(): StatItem[] {
        const items = props.block.props.items
        if (!Array.isArray(items) || !items.length) {
            return [
                { value: '10+', label: 'Years' },
                { value: '50+', label: 'Projects' },
                { value: '98%', label: 'Occupancy' },
            ]
        }
        return items.map((row) => {
            const item = (row && typeof row === 'object') ? row as Record<string, unknown> : {}
            return {
                value: String(item.value ?? ''),
                label: String(item.label ?? ''),
            }
        })
    },
    set(next: StatItem[]) {
        props.block.props.items = next
        touch()
    },
})

const socialItems = computed({
    get(): SocialItem[] {
        const items = props.block.props.items
        if (!Array.isArray(items)) return []
        return items.map((row) => {
            const item = (row && typeof row === 'object') ? row as Record<string, unknown> : {}
            return {
                label: String(item.label ?? ''),
                url: String(item.url ?? ''),
                icon: String(item.icon ?? ''),
            }
        })
    },
    set(next: SocialItem[]) {
        props.block.props.items = next
        touch()
    },
})

const timelineItems = computed({
    get(): TimelineItem[] {
        const items = props.block.props.items
        if (!Array.isArray(items)) return []
        return items.map((row) => {
            const item = (row && typeof row === 'object') ? row as Record<string, unknown> : {}
            const bullets = Array.isArray(item.bullets) ? item.bullets.map(String) : []
            return {
                date: String(item.date ?? ''),
                title: String(item.title ?? ''),
                organization: String(item.organization ?? ''),
                bullets,
            }
        })
    },
    set(next: TimelineItem[]) {
        props.block.props.items = next
        touch()
    },
})

const galleryItems = computed({
    get(): GalleryItem[] {
        const images = props.block.props.images
        if (!Array.isArray(images)) return []
        return images.map((row) => {
            const item = (row && typeof row === 'object') ? row as Record<string, unknown> : {}
            return {
                src: String(item.src ?? ''),
                alt: String(item.alt ?? ''),
            }
        })
    },
    set(next: GalleryItem[]) {
        props.block.props.images = next
        touch()
    },
})

function setScalar(key: string, value: string): void {
    const current = props.block.props[key]
    if (typeof current === 'number') {
        const parsed = Number(value)
        props.block.props[key] = Number.isFinite(parsed) ? parsed : value
    } else if (typeof current === 'boolean') {
        props.block.props[key] = value === 'true' || value === '1'
    } else {
        props.block.props[key] = value
    }
    touch()
}

function updateStat(index: number, key: keyof StatItem, value: string): void {
    const next = statsItems.value.map((row, i) => (i === index ? { ...row, [key]: value } : row))
    statsItems.value = next
}

function addStat(): void {
    statsItems.value = [...statsItems.value, { value: '0', label: 'Label' }]
}

function removeStat(index: number): void {
    statsItems.value = statsItems.value.filter((_, i) => i !== index)
}

function updateSocial(index: number, key: keyof SocialItem, value: string): void {
    socialItems.value = socialItems.value.map((row, i) => (i === index ? { ...row, [key]: value } : row))
}

function addSocial(): void {
    socialItems.value = [...socialItems.value, { label: 'Link', url: '#', icon: 'website' }]
}

function removeSocial(index: number): void {
    socialItems.value = socialItems.value.filter((_, i) => i !== index)
}

function openIconPicker(index: number): void {
    iconPickerIndex.value = index
    iconPickerOpen.value = true
}

function onIconPicked(slug: string): void {
    updateSocial(iconPickerIndex.value, 'icon', slug)
}

function updateTimeline(index: number, key: 'date' | 'title' | 'organization' | 'bullets', value: string): void {
    timelineItems.value = timelineItems.value.map((row, i) => {
        if (i !== index) return row
        if (key === 'bullets') {
            return { ...row, bullets: value.split('\n').map((line) => line.trim()).filter(Boolean) }
        }
        return { ...row, [key]: value }
    })
}

function addTimeline(): void {
    timelineItems.value = [...timelineItems.value, { date: '2024', title: 'Role', organization: 'Company', bullets: ['Achievement'] }]
}

function removeTimeline(index: number): void {
    timelineItems.value = timelineItems.value.filter((_, i) => i !== index)
}

function updateGallery(index: number, key: keyof GalleryItem, value: string): void {
    galleryItems.value = galleryItems.value.map((row, i) => (i === index ? { ...row, [key]: value } : row))
}

function addGallery(): void {
    galleryItems.value = [...galleryItems.value, { src: '', alt: '' }]
}

function removeGallery(index: number): void {
    galleryItems.value = galleryItems.value.filter((_, i) => i !== index)
}
</script>

<template>
    <div class="space-y-4">
        <div v-if="block.type === 'image'" class="space-y-3">
            <div class="space-y-2">
                <Label>Image</Label>
                <div v-if="block.props.src" class="overflow-hidden rounded-lg border">
                    <img :src="String(block.props.src)" :alt="String(block.props.alt || '')" class="max-h-40 w-full object-cover">
                </div>
                <div class="flex flex-wrap gap-2">
                    <Button size="sm" variant="outline" @click="emit('pickMedia', { field: 'src' })">
                        Choose from library
                    </Button>
                </div>
                <p class="text-muted-foreground text-xs">Or drag a photo from your computer onto the image block on the canvas.</p>
            </div>
        </div>

        <div v-for="key in scalarKeys" :key="key" class="space-y-2">
            <Label :for="`prop-${block.id}-${key}`" class="capitalize">{{ key.replace(/_/g, ' ') }}</Label>
            <textarea
                v-if="isMultiline(key)"
                :id="`prop-${block.id}-${key}`"
                class="border-input bg-background focus-visible:ring-ring flex min-h-28 w-full rounded-md border px-3 py-2 text-sm shadow-xs outline-none focus-visible:ring-2"
                :value="String(block.props[key] ?? '')"
                @input="setScalar(key, ($event.target as HTMLTextAreaElement).value)"
            />
            <Input
                v-else
                :id="`prop-${block.id}-${key}`"
                :model-value="String(block.props[key] ?? '')"
                @update:model-value="setScalar(key, String($event))"
            />
        </div>

        <div v-if="block.type === 'stats-row'" class="space-y-3">
            <div class="flex items-center justify-between gap-2">
                <Label>Stats</Label>
                <Button size="sm" variant="outline" class="gap-1" @click="addStat">
                    <Plus class="size-3.5" />
                    Add
                </Button>
            </div>
            <div
                v-for="(item, index) in statsItems"
                :key="index"
                class="space-y-2 rounded-lg border p-3"
            >
                <div class="flex items-start justify-between gap-2">
                    <p class="text-muted-foreground text-xs font-medium tracking-wide uppercase">Stat {{ index + 1 }}</p>
                    <Button size="sm" variant="ghost" class="text-destructive h-7 px-2" @click="removeStat(index)">
                        <Trash2 class="size-3.5" />
                    </Button>
                </div>
                <div class="space-y-1">
                    <Label :for="`stat-value-${index}`">Value</Label>
                    <Input :id="`stat-value-${index}`" :model-value="item.value" @update:model-value="updateStat(index, 'value', String($event))" />
                </div>
                <div class="space-y-1">
                    <Label :for="`stat-label-${index}`">Label</Label>
                    <Input :id="`stat-label-${index}`" :model-value="item.label" @update:model-value="updateStat(index, 'label', String($event))" />
                </div>
            </div>
        </div>

        <div v-else-if="block.type === 'social-links'" class="space-y-3">
            <div class="space-y-2">
                <Label>Display style</Label>
                <div class="grid grid-cols-2 gap-2">
                    <button
                        v-for="style in socialVariants"
                        :key="style.key"
                        type="button"
                        class="hover:border-primary rounded-lg border px-2 py-1.5 text-left text-xs transition"
                        :class="socialVariant === style.key ? 'border-primary ring-primary/30 ring-1' : ''"
                        @click="socialVariant = style.key"
                    >
                        {{ style.label }}
                    </button>
                </div>
            </div>
            <div class="flex items-center justify-between gap-2">
                <Label>Links</Label>
                <Button size="sm" variant="outline" class="gap-1" @click="addSocial">
                    <Plus class="size-3.5" />
                    Add
                </Button>
            </div>
            <div v-for="(item, index) in socialItems" :key="index" class="space-y-2 rounded-lg border p-3">
                <div class="flex items-center justify-between gap-2">
                    <button
                        type="button"
                        class="hover:border-primary flex items-center gap-2 rounded-lg border px-2 py-1.5 text-xs transition"
                        @click="openIconPicker(index)"
                    >
                        <SocialBrandIcon :slug="item.icon" :label="item.label" :url="item.url" :size="16" />
                        {{ item.icon || 'Pick icon' }}
                    </button>
                    <Button size="sm" variant="ghost" class="text-destructive h-7 px-2" @click="removeSocial(index)">
                        <Trash2 class="size-3.5" />
                    </Button>
                </div>
                <Input :model-value="item.label" placeholder="Label" @update:model-value="updateSocial(index, 'label', String($event))" />
                <Input :model-value="item.url" placeholder="https://" @update:model-value="updateSocial(index, 'url', String($event))" />
            </div>
            <IconPicker
                v-model:open="iconPickerOpen"
                :model-value="socialItems[iconPickerIndex]?.icon || null"
                title="Social icon"
                @update:model-value="onIconPicked"
            />
        </div>

        <div v-else-if="block.type === 'timeline'" class="space-y-3">
            <div class="flex items-center justify-between gap-2">
                <Label>Entries</Label>
                <Button size="sm" variant="outline" class="gap-1" @click="addTimeline">
                    <Plus class="size-3.5" />
                    Add
                </Button>
            </div>
            <div v-for="(item, index) in timelineItems" :key="index" class="space-y-2 rounded-lg border p-3">
                <div class="flex justify-end">
                    <Button size="sm" variant="ghost" class="text-destructive h-7 px-2" @click="removeTimeline(index)">
                        <Trash2 class="size-3.5" />
                    </Button>
                </div>
                <Input :model-value="item.date" placeholder="Date" @update:model-value="updateTimeline(index, 'date', String($event))" />
                <Input :model-value="item.title" placeholder="Title" @update:model-value="updateTimeline(index, 'title', String($event))" />
                <Input :model-value="item.organization" placeholder="Organization" @update:model-value="updateTimeline(index, 'organization', String($event))" />
                <textarea
                    class="border-input bg-background focus-visible:ring-ring flex min-h-20 w-full rounded-md border px-3 py-2 text-sm outline-none focus-visible:ring-2"
                    :value="item.bullets.join('\n')"
                    placeholder="One bullet per line"
                    @input="updateTimeline(index, 'bullets', ($event.target as HTMLTextAreaElement).value)"
                />
            </div>
        </div>

        <div v-else-if="block.type === 'gallery-grid'" class="space-y-3">
            <div class="flex items-center justify-between gap-2">
                <Label>Images</Label>
                <Button size="sm" variant="outline" class="gap-1" @click="addGallery">
                    <Plus class="size-3.5" />
                    Add
                </Button>
            </div>
            <div v-for="(item, index) in galleryItems" :key="index" class="space-y-2 rounded-lg border p-3">
                <div class="flex justify-end">
                    <Button size="sm" variant="ghost" class="text-destructive h-7 px-2" @click="removeGallery(index)">
                        <Trash2 class="size-3.5" />
                    </Button>
                </div>
                <div v-if="item.src" class="overflow-hidden rounded border">
                    <img :src="item.src" :alt="item.alt" class="h-24 w-full object-cover">
                </div>
                <Button size="sm" variant="outline" class="w-full" @click="emit('pickMedia', { field: 'images', index })">
                    Choose from library
                </Button>
                <Input :model-value="item.alt" placeholder="Alt text" @update:model-value="updateGallery(index, 'alt', String($event))" />
            </div>
        </div>
    </div>
</template>
