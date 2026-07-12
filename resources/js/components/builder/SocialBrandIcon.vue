<script setup lang="ts">
import { computed } from 'vue'
import { cdnIconUrl, getLocalIcon, guessIconSlug, iconSvgMarkup, isLocalIconSlug } from '@/lib/simpleIconsCatalog'

const props = withDefaults(defineProps<{
    slug?: string | null
    label?: string
    url?: string
    colored?: boolean
    size?: number
}>(), {
    slug: null,
    label: '',
    url: '',
    colored: true,
    size: 20,
})

const resolvedSlug = computed(() => {
    const explicit = (props.slug || '').trim()
    if (explicit) return explicit
    return guessIconSlug(props.label, props.url)
})

const local = computed(() => getLocalIcon(resolvedSlug.value))

const markup = computed(() => {
    if (!local.value) return ''
    return iconSvgMarkup(local.value, props.colored)
})

const cdnSrc = computed(() => {
    if (isLocalIconSlug(resolvedSlug.value)) return ''
    return cdnIconUrl(resolvedSlug.value)
})
</script>

<template>
    <span
        class="dc-social-icon inline-flex shrink-0 items-center justify-center"
        :style="{ width: `${size}px`, height: `${size}px` }"
        aria-hidden="true"
    >
        <span v-if="local" class="contents" v-html="markup" />
        <img
            v-else
            :src="cdnSrc"
            alt=""
            width="24"
            height="24"
            loading="lazy"
            decoding="async"
            class="h-full w-full"
        >
    </span>
</template>

<style scoped>
.dc-social-icon :deep(svg) {
    display: block;
    height: 100%;
    width: 100%;
}
</style>
