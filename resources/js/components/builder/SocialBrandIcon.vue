<script setup lang="ts">
import { computed } from 'vue'
import { getBrandIcon, guessIconSlug, iconSvgMarkup } from '@/lib/simpleIconsCatalog'

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

const icon = computed(() => {
    const resolved = props.slug || guessIconSlug(props.label, props.url)
    return getBrandIcon(resolved)
})

const markup = computed(() => {
    if (!icon.value) return ''
    return iconSvgMarkup(icon.value, props.colored)
})
</script>

<template>
    <span
        class="dc-social-icon inline-flex shrink-0 items-center justify-center"
        :style="{ width: `${size}px`, height: `${size}px` }"
        aria-hidden="true"
        v-html="markup"
    />
</template>

<style scoped>
.dc-social-icon :deep(svg) {
    display: block;
    height: 100%;
    width: 100%;
}
</style>
