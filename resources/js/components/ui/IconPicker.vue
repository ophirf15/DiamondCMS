<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref, watch } from 'vue'
import { Check, Search, X } from '@lucide/vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import {
    type BrandIcon,
    POPULAR_META,
    cdnIconUrl,
    getLocalIcon,
    iconSvgMarkup,
    isLocalIconSlug,
    loadBrandIconCatalog,
    searchBrandIconsSync,
} from '@/lib/simpleIconsCatalog'

const open = defineModel<boolean>('open', { default: false })

const props = withDefaults(defineProps<{
    modelValue?: string | null
    title?: string
}>(), {
    modelValue: null,
    title: 'Pick an icon',
})

const emit = defineEmits<{
    'update:modelValue': [slug: string]
    select: [icon: BrandIcon]
}>()

const query = ref('')
const colored = ref(true)
const loading = ref(false)
const catalog = ref<BrandIcon[]>([])
const loadError = ref('')

const results = computed(() => searchBrandIconsSync(query.value, catalog.value, 96))

const selectedMeta = computed(() => {
    const slug = props.modelValue
    if (!slug) return null
    const local = getLocalIcon(slug)
    if (local) return local
    return catalog.value.find((icon) => icon.slug === slug)
        || POPULAR_META.find((row) => row.slug === slug)
        || { slug, title: slug, hex: '888888' }
})

watch(open, async (isOpen) => {
    if (!isOpen) return
    query.value = ''
    loadError.value = ''
    if (catalog.value.length) return
    loading.value = true
    try {
        catalog.value = await loadBrandIconCatalog()
    } catch (error) {
        loadError.value = error instanceof Error ? error.message : 'Could not load icon catalog'
    } finally {
        loading.value = false
    }
})

function onKey(event: KeyboardEvent): void {
    if (event.key === 'Escape' && open.value) open.value = false
}

onMounted(() => window.addEventListener('keydown', onKey))
onUnmounted(() => window.removeEventListener('keydown', onKey))

function pick(icon: { slug: string, title: string, hex: string, path?: string, source?: 'simple-icons' | 'local' }): void {
    const full: BrandIcon = {
        slug: icon.slug,
        title: icon.title,
        hex: icon.hex,
        path: icon.path || '',
        source: icon.source || (isLocalIconSlug(icon.slug) ? 'local' : 'simple-icons'),
    }
    emit('update:modelValue', full.slug)
    emit('select', full)
    open.value = false
}

function glyph(icon: { slug: string, title: string, hex: string, path?: string }): string {
    const local = getLocalIcon(icon.slug)
    if (local) return iconSvgMarkup(local, colored.value)
    if (icon.path) {
        return iconSvgMarkup({
            slug: icon.slug,
            title: icon.title,
            hex: icon.hex,
            path: icon.path,
            source: 'simple-icons',
        }, colored.value)
    }
    const src = colored.value ? cdnIconUrl(icon.slug, icon.hex) : cdnIconUrl(icon.slug)
    return `<img src="${src}" alt="" width="24" height="24" loading="lazy" decoding="async" />`
}
</script>

<template>
    <Teleport to="body">
        <div
            v-if="open"
            class="fixed inset-0 z-50 flex items-end justify-center bg-black/50 p-4 sm:items-center"
            role="dialog"
            aria-modal="true"
            :aria-label="title"
            @click.self="open = false"
        >
            <div class="bg-background flex max-h-[90vh] w-full max-w-2xl flex-col overflow-hidden rounded-2xl border shadow-2xl">
                <div class="flex items-start justify-between gap-3 border-b px-5 py-4">
                    <div class="min-w-0 space-y-1">
                        <h2 class="text-base font-semibold">{{ title }}</h2>
                        <p class="text-muted-foreground text-xs leading-relaxed">
                            Icons from
                            <a href="https://simpleicons.org/?q=list" class="text-primary underline-offset-2 hover:underline" target="_blank" rel="noopener">Simple Icons</a>.
                            LinkedIn and a few generics use a local fallback.
                        </p>
                    </div>
                    <Button size="sm" variant="ghost" class="shrink-0 px-2" aria-label="Close" @click="open = false">
                        <X class="size-4" />
                    </Button>
                </div>

                <div class="space-y-3 overflow-y-auto px-5 py-4">
                    <div class="relative">
                        <Search class="text-muted-foreground pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2" />
                        <Input
                            v-model="query"
                            class="pl-9"
                            placeholder="Search brands… (instagram, github, x…)"
                            autofocus
                        />
                    </div>
                    <label class="text-muted-foreground flex items-center gap-2 text-xs">
                        <input v-model="colored" type="checkbox" class="size-3.5 rounded border">
                        Brand colors
                    </label>

                    <p v-if="loading" class="text-muted-foreground text-sm">Loading icon catalog…</p>
                    <p v-else-if="loadError" class="text-destructive text-sm">{{ loadError }}</p>

                    <div v-if="!query.trim()" class="space-y-2">
                        <p class="text-muted-foreground text-xs font-medium tracking-wide uppercase">Popular social</p>
                        <div class="grid grid-cols-4 gap-2 sm:grid-cols-6">
                            <button
                                v-for="icon in POPULAR_META"
                                :key="icon.slug"
                                type="button"
                                class="hover:border-primary relative flex flex-col items-center gap-1.5 rounded-xl border p-2.5 text-center transition"
                                :class="modelValue === icon.slug ? 'border-primary ring-primary/30 ring-2' : ''"
                                :title="icon.title"
                                @click="pick(icon)"
                            >
                                <span class="dc-icon-picker-glyph size-6" v-html="glyph(icon)" />
                                <span class="line-clamp-1 w-full text-[10px] leading-tight">{{ icon.title }}</span>
                                <Check v-if="modelValue === icon.slug" class="text-primary absolute top-1 right-1 size-3" />
                            </button>
                        </div>
                    </div>

                    <div class="max-h-72 overflow-y-auto rounded-xl border">
                        <div class="grid grid-cols-3 gap-1 p-2 sm:grid-cols-4">
                            <button
                                v-for="icon in results"
                                :key="`all-${icon.slug}`"
                                type="button"
                                class="hover:bg-muted/60 relative flex items-center gap-2 rounded-lg px-2 py-2 text-left transition"
                                :class="modelValue === icon.slug ? 'bg-muted' : ''"
                                @click="pick(icon)"
                            >
                                <span class="dc-icon-picker-glyph size-5 shrink-0" v-html="glyph(icon)" />
                                <span class="min-w-0 flex-1">
                                    <span class="block truncate text-xs font-medium">{{ icon.title }}</span>
                                    <span class="text-muted-foreground block truncate text-[10px]">{{ icon.slug }}</span>
                                </span>
                            </button>
                        </div>
                        <p v-if="!loading && !results.length" class="text-muted-foreground p-6 text-center text-sm">No icons match that search.</p>
                    </div>

                    <div v-if="selectedMeta" class="bg-muted/40 flex items-center gap-3 rounded-lg border px-3 py-2 text-sm">
                        <span class="dc-icon-picker-glyph size-5" v-html="glyph(selectedMeta)" />
                        <span class="min-w-0 flex-1 truncate">Selected: <strong>{{ selectedMeta.title }}</strong> <span class="text-muted-foreground">({{ selectedMeta.slug }})</span></span>
                        <Button size="sm" variant="ghost" @click="open = false">Done</Button>
                    </div>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<style scoped>
.dc-icon-picker-glyph :deep(svg),
.dc-icon-picker-glyph :deep(img) {
    display: block;
    height: 100%;
    width: 100%;
}
</style>
