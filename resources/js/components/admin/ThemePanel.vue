<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { Plus, Trash2 } from '@lucide/vue'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import IconPicker from '@/components/ui/IconPicker.vue'
import SocialBrandIcon from '@/components/builder/SocialBrandIcon.vue'
import { showActionToast } from '@/lib/actionToast'

type SocialLinkItem = { label: string, url: string, icon: string }

type DesignTokens = {
    mode: string
    colors: Record<string, string>
    dark: Record<string, string>
    typography: { body: string, heading: string, scale: number }
    branding: { logo: string | null, alternateLogo: string | null, favicon: string | null }
    spacing?: { container: string, radius: string, headerPadY?: string, headerPadX?: string }
    atmosphere?: { preset: string, custom: string }
    chrome?: {
        headerStyle: string
        footerStyle: string
        footerShowLogo: boolean
        footerShowSiteName: boolean
        footerTagline: string
        footerShowCredit?: boolean
        footerCreditText?: string
        footerCreditUrl?: string
        footerSocials?: SocialLinkItem[]
        footerSocialStyle?: string
    }
    buttons?: { style: string }
    uiKit?: {
        radiusPreset: string
        surface: string
        density: string
        controlStyle: string
        socialStyle: string
    }
    themeControl?: {
        allowVisitorToggle: boolean
        lockMode: boolean
    }
}

type AtmospherePreset = {
    key: string
    label: string
    preview: string
}

type StylePreset = {
    key: string
    label: string
    description: string
}

const props = defineProps<{
    api: <T>(url: string, options?: RequestInit) => Promise<T>
}>()

const tokens = ref<DesignTokens | null>(null)
const saving = ref(false)

const brandDefaults = [
    { label: 'Gold mark', path: '/brand/logo-primary-gold.svg' },
    { label: 'Current color', path: '/brand/logo-currentColor.svg' },
    { label: 'White', path: '/brand/logo-white.svg' },
    { label: 'Black', path: '/brand/logo-black.svg' },
    { label: 'Gold on charcoal', path: '/brand/logo-gold-on-charcoal.svg' },
]

const fontPresets = [
    { label: 'Sora + Fraunces (default)', body: "'Sora', ui-sans-serif, system-ui, sans-serif", heading: "'Fraunces', Georgia, serif" },
    { label: 'System sans', body: "ui-sans-serif, system-ui, sans-serif", heading: "ui-sans-serif, system-ui, sans-serif" },
    { label: 'Georgia editorial', body: "Georgia, 'Times New Roman', serif", heading: "Georgia, 'Times New Roman', serif" },
]

const atmospherePresets: AtmospherePreset[] = [
    { key: 'solid', label: 'Solid color', preview: 'var(--preview-bg, #111)' },
    { key: 'soft-teal', label: 'Soft teal wash', preview: 'radial-gradient(ellipse 80% 50% at 0% -10%, rgb(13 92 77 / 35%), transparent 55%), #121714' },
    { key: 'navy', label: 'Navy AI gradient', preview: 'radial-gradient(circle at 20% 18%, #12325a 0%, #07101f 42%, #050a15 78%)' },
    { key: 'midnight', label: 'Midnight charcoal', preview: 'radial-gradient(ellipse 70% 50% at 50% -20%, #1f2937 0%, #0a0a0a 55%)' },
    { key: 'split-teal', label: 'Deep teal panel', preview: 'linear-gradient(115deg, #0b3d3d 0%, #062828 48%, #101010 48%)' },
    { key: 'custom', label: 'Custom CSS', preview: 'repeating-linear-gradient(45deg, #222 0 8px, #111 8px 16px)' },
]

const headerStyles: StylePreset[] = [
    { key: 'classic', label: 'Classic', description: 'Logo left, links right' },
    { key: 'pill', label: 'Pill nav', description: 'Centered capsule menu' },
    { key: 'minimal', label: 'Minimal', description: 'Thin bar, quiet links' },
    { key: 'centered', label: 'Centered brand', description: 'Logo above links' },
    { key: 'split', label: 'Split CTA', description: 'Links left, action right' },
]

const footerStyles: StylePreset[] = [
    { key: 'minimal', label: 'Minimal', description: 'Compact link row' },
    { key: 'branded', label: 'Branded', description: 'Logo + links + credit' },
    { key: 'split', label: 'Split', description: 'Brand left, links right' },
    { key: 'centered', label: 'Centered', description: 'Stacked centered footer' },
]

const buttonStyles: StylePreset[] = [
    { key: 'solid', label: 'Solid', description: 'Filled primary' },
    { key: 'soft', label: 'Soft', description: 'Tinted, no hard edge' },
    { key: 'outline', label: 'Outline', description: 'Bordered transparent' },
    { key: 'pill', label: 'Pill', description: 'Fully rounded solid' },
    { key: 'ghost', label: 'Ghost', description: 'Text with subtle hover' },
    { key: 'underline', label: 'Underline', description: 'Link-style accent' },
]

const radiusPresets: StylePreset[] = [
    { key: 'sm', label: 'Sharp', description: 'Subtle corners' },
    { key: 'md', label: 'Balanced', description: 'Default shadcn feel' },
    { key: 'lg', label: 'Soft', description: 'HeroUI-like rounded' },
    { key: 'xl', label: 'Plush', description: 'Very rounded panels' },
    { key: 'full', label: 'Pill', description: 'Capsule controls' },
]

const surfacePresets: StylePreset[] = [
    { key: 'flat', label: 'Flat', description: 'No elevation' },
    { key: 'soft', label: 'Soft', description: 'Tinted panels, light blur' },
    { key: 'elevated', label: 'Elevated', description: 'Deeper shadow cards' },
]

const socialStyles: StylePreset[] = [
    { key: 'list', label: 'Text list', description: 'Classic dotted list' },
    { key: 'icons', label: 'Icons only', description: 'Brand marks in a row' },
    { key: 'icons-labels', label: 'Icons + labels', description: 'Icon beside name' },
    { key: 'pills', label: 'Pills', description: 'Soft rounded chips' },
]

const densityPresets: StylePreset[] = [
    { key: 'comfortable', label: 'Comfortable', description: 'Roomy chrome padding' },
    { key: 'compact', label: 'Compact', description: 'Tighter header spacing' },
]

const controlPresets: StylePreset[] = [
    { key: 'solid', label: 'Solid', description: 'Filled controls' },
    { key: 'soft', label: 'Soft', description: 'HeroUI soft fill' },
    { key: 'bordered', label: 'Bordered', description: 'Outline emphasis' },
]

const footerSocialIconOpen = ref(false)
const footerSocialIconIndex = ref(0)

const navyDark = {
    background: '#050a15',
    foreground: '#eef5ff',
    muted: '#9eb0c8',
    primary: '#00a3ff',
    primaryContrast: '#041018',
    surface: '#0b1524',
    accent: '#4cc9f0',
}

const midnightDark = {
    background: '#0a0a0a',
    foreground: '#f4f4f4',
    muted: '#a3a3a3',
    primary: '#b8ff3c',
    primaryContrast: '#041004',
    surface: '#141414',
    accent: '#84cc16',
}

const splitTealDark = {
    background: '#062828',
    foreground: '#f4fff8',
    muted: '#a7c4bc',
    primary: '#2dd4bf',
    primaryContrast: '#042f2e',
    surface: '#0d3333',
    accent: '#a3e635',
}

function ensureChrome(tokensValue: DesignTokens): void {
    if (!tokensValue.chrome) {
        tokensValue.chrome = {
            headerStyle: 'classic',
            footerStyle: 'branded',
            footerShowLogo: true,
            footerShowSiteName: true,
            footerTagline: '',
            footerShowCredit: true,
            footerCreditText: 'Powered by DiamondCMS',
            footerCreditUrl: '',
            footerSocials: [],
            footerSocialStyle: 'icons',
        }
    } else {
        tokensValue.chrome.footerShowCredit ??= true
        tokensValue.chrome.footerCreditText ??= 'Powered by DiamondCMS'
        tokensValue.chrome.footerCreditUrl ??= ''
        tokensValue.chrome.footerSocials ??= []
        tokensValue.chrome.footerSocialStyle ??= 'icons'
    }
    if (!tokensValue.buttons) {
        tokensValue.buttons = { style: 'solid' }
    }
    if (!tokensValue.uiKit) {
        tokensValue.uiKit = {
            radiusPreset: 'md',
            surface: 'soft',
            density: 'comfortable',
            controlStyle: 'soft',
            socialStyle: 'icons-labels',
        }
    } else {
        tokensValue.uiKit.radiusPreset ??= 'md'
        tokensValue.uiKit.surface ??= 'soft'
        tokensValue.uiKit.density ??= 'comfortable'
        tokensValue.uiKit.controlStyle ??= 'soft'
        tokensValue.uiKit.socialStyle ??= 'icons-labels'
    }
    if (!tokensValue.themeControl) {
        tokensValue.themeControl = { allowVisitorToggle: true, lockMode: false }
    }
    if (!tokensValue.atmosphere) {
        tokensValue.atmosphere = { preset: 'soft-teal', custom: '' }
    }
    if (!tokensValue.spacing) {
        tokensValue.spacing = { container: '1120px', radius: '0.4rem', headerPadY: '1.35rem', headerPadX: '1.5rem' }
    } else {
        tokensValue.spacing.headerPadY ??= '1.35rem'
        tokensValue.spacing.headerPadX ??= '1.5rem'
    }
}

const footerSocials = computed({
    get(): SocialLinkItem[] {
        return tokens.value?.chrome?.footerSocials ?? []
    },
    set(next: SocialLinkItem[]) {
        if (!tokens.value?.chrome) return
        tokens.value.chrome.footerSocials = next
    },
})

function addFooterSocial(): void {
    footerSocials.value = [...footerSocials.value, { label: 'Instagram', url: 'https://instagram.com', icon: 'instagram' }]
}

function removeFooterSocial(index: number): void {
    footerSocials.value = footerSocials.value.filter((_, i) => i !== index)
}

function updateFooterSocial(index: number, key: keyof SocialLinkItem, value: string): void {
    footerSocials.value = footerSocials.value.map((row, i) => (i === index ? { ...row, [key]: value } : row))
}

function openFooterIconPicker(index: number): void {
    footerSocialIconIndex.value = index
    footerSocialIconOpen.value = true
}

function onFooterIconPicked(slug: string): void {
    updateFooterSocial(footerSocialIconIndex.value, 'icon', slug)
}

const previewStyle = computed(() => {
    if (!tokens.value) return {}
    const palette = tokens.value.mode === 'dark' ? tokens.value.dark : tokens.value.colors
    const preset = tokens.value.atmosphere?.preset || 'soft-teal'
    const atmosphere = atmospherePresets.find((row) => row.key === preset)
    let background = atmosphere?.preview || palette.background
    if (preset === 'solid') background = palette.background
    if (preset === 'custom' && tokens.value.atmosphere?.custom) background = tokens.value.atmosphere.custom

    return {
        background,
        color: palette.foreground,
        fontFamily: tokens.value.typography.body,
        '--preview-bg': palette.background,
        '--preview-primary': palette.primary,
        '--preview-primary-contrast': palette.primaryContrast || '#fff',
        '--preview-accent': palette.accent,
        '--preview-surface': palette.surface,
        '--preview-heading': tokens.value.typography.heading,
        '--preview-muted': palette.muted,
    } as Record<string, string>
})

const buttonPreviewClass = computed(() => {
    const style = tokens.value?.buttons?.style || 'solid'
    return `dc-preview-btn dc-preview-btn--${style}`
})

async function load(): Promise<void> {
    try {
        const loaded = await props.api<DesignTokens>('/design')
        ensureChrome(loaded)
        tokens.value = loaded
    } catch (error) {
        showActionToast(null, error instanceof Error ? error.message : 'Could not load theme', 'error')
    }
}

async function save(event?: Event): Promise<void> {
    if (!tokens.value) return
    saving.value = true
    try {
        const saved = await props.api<DesignTokens>('/design', {
            method: 'PUT',
            body: JSON.stringify({ tokens: tokens.value }),
        })
        ensureChrome(saved)
        tokens.value = saved
        showActionToast(event, 'Theme saved — public site updated')
    } catch (error) {
        showActionToast(event, error instanceof Error ? error.message : 'Could not save theme', 'error')
    } finally {
        saving.value = false
    }
}

function applyFont(preset: typeof fontPresets[number]): void {
    if (!tokens.value) return
    tokens.value.typography.body = preset.body
    tokens.value.typography.heading = preset.heading
}

function applyAtmosphere(key: string): void {
    if (!tokens.value) return
    if (!tokens.value.atmosphere) {
        tokens.value.atmosphere = { preset: key, custom: '' }
    } else {
        tokens.value.atmosphere.preset = key
    }

    if (key === 'navy') {
        tokens.value.mode = 'dark'
        tokens.value.dark = { ...tokens.value.dark, ...navyDark }
    } else if (key === 'midnight') {
        tokens.value.mode = 'dark'
        tokens.value.dark = { ...tokens.value.dark, ...midnightDark }
    } else if (key === 'split-teal') {
        tokens.value.mode = 'dark'
        tokens.value.dark = { ...tokens.value.dark, ...splitTealDark }
    }
}

function onLockChange(): void {
    if (!tokens.value?.themeControl) return
    if (tokens.value.themeControl.lockMode) {
        tokens.value.themeControl.allowVisitorToggle = false
    }
}

onMounted(load)
</script>

<template>
    <section v-if="tokens" class="space-y-4">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <h1 class="text-3xl font-semibold tracking-tight">Theme</h1>
                <p class="text-muted-foreground text-sm">
                    Tune atmosphere, chrome, spacing, and colors. The wide preview stays with you as you scroll.
                </p>
            </div>
            <Button :disabled="saving" @click="save($event)">Save theme</Button>
        </div>

        <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_minmax(420px,46%)]">
            <div class="space-y-4">
                <Card>
                    <CardHeader>
                        <CardTitle>Page atmosphere</CardTitle>
                        <CardDescription>
                            Background look for the public site. Pick <strong>Navy AI gradient</strong> to match the Dark applied-AI hero template.
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                            <button
                                v-for="preset in atmospherePresets"
                                :key="preset.key"
                                type="button"
                                class="hover:border-primary overflow-hidden rounded-xl border text-left transition"
                                :class="tokens.atmosphere?.preset === preset.key ? 'border-primary ring-primary/30 ring-2' : ''"
                                @click="applyAtmosphere(preset.key)"
                            >
                                <div class="h-16 w-full" :style="{ background: preset.preview }" />
                                <div class="px-3 py-2 text-sm font-medium">{{ preset.label }}</div>
                            </button>
                        </div>
                        <div v-if="tokens.atmosphere?.preset === 'custom'" class="space-y-2">
                            <Label for="custom-bg">Custom background CSS</Label>
                            <Input
                                id="custom-bg"
                                v-model="tokens.atmosphere.custom"
                                placeholder="radial-gradient(circle at 20% 20%, #12325a, #050a15)"
                            />
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Header style</CardTitle>
                        <CardDescription>Pick a top menu layout. Link labels are edited under Menus.</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                            <button
                                v-for="style in headerStyles"
                                :key="style.key"
                                type="button"
                                class="hover:border-primary overflow-hidden rounded-xl border text-left transition"
                                :class="tokens.chrome?.headerStyle === style.key ? 'border-primary ring-primary/30 ring-2' : ''"
                                @click="tokens.chrome && (tokens.chrome.headerStyle = style.key)"
                            >
                                <div class="bg-muted/40 flex h-14 items-center gap-2 px-3" :class="{
                                    'justify-between': style.key === 'classic' || style.key === 'minimal' || style.key === 'pill' || style.key === 'split',
                                    'flex-col justify-center': style.key === 'centered',
                                }">
                                    <template v-if="style.key === 'centered'">
                                        <span class="bg-foreground/80 h-2 w-10 rounded-sm" />
                                        <span class="bg-foreground/30 h-1.5 w-20 rounded-full" />
                                    </template>
                                    <template v-else-if="style.key === 'pill'">
                                        <span class="bg-foreground/70 h-2 w-8 rounded-sm" />
                                        <span class="bg-background border-foreground/20 h-5 w-24 rounded-full border" />
                                    </template>
                                    <template v-else-if="style.key === 'split'">
                                        <span class="bg-foreground/25 h-1.5 w-16 rounded-full" />
                                        <span class="bg-foreground/70 h-2 w-8 rounded-sm" />
                                        <span class="bg-primary h-4 w-10 rounded-sm" />
                                    </template>
                                    <template v-else>
                                        <span class="bg-foreground/70 h-2 w-8 rounded-sm" />
                                        <span class="bg-foreground/25 h-1.5 w-16 rounded-full" :class="style.key === 'minimal' ? 'opacity-60' : ''" />
                                    </template>
                                </div>
                                <div class="px-3 py-2">
                                    <div class="text-sm font-medium">{{ style.label }}</div>
                                    <div class="text-muted-foreground text-xs">{{ style.description }}</div>
                                </div>
                            </button>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Footer style</CardTitle>
                        <CardDescription>Choose footer layout and whether your logo appears.</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="grid gap-3 sm:grid-cols-2">
                            <button
                                v-for="style in footerStyles"
                                :key="style.key"
                                type="button"
                                class="hover:border-primary overflow-hidden rounded-xl border text-left transition"
                                :class="tokens.chrome?.footerStyle === style.key ? 'border-primary ring-primary/30 ring-2' : ''"
                                @click="tokens.chrome && (tokens.chrome.footerStyle = style.key)"
                            >
                                <div class="bg-muted/40 flex h-14 flex-col justify-center gap-1.5 px-3" :class="{
                                    'items-center': style.key === 'centered',
                                    'items-start': style.key === 'minimal' || style.key === 'branded',
                                    'flex-row items-center justify-between': style.key === 'split',
                                }">
                                    <span v-if="style.key !== 'minimal'" class="bg-foreground/70 h-2 w-10 rounded-sm" />
                                    <span class="bg-foreground/25 h-1.5 w-16 rounded-full" />
                                </div>
                                <div class="px-3 py-2">
                                    <div class="text-sm font-medium">{{ style.label }}</div>
                                    <div class="text-muted-foreground text-xs">{{ style.description }}</div>
                                </div>
                            </button>
                        </div>
                        <div class="grid gap-3 sm:grid-cols-2">
                            <label class="flex items-center gap-2 text-sm">
                                <input v-model="tokens.chrome!.footerShowLogo" type="checkbox" class="size-4 rounded border">
                                Show logo in footer
                            </label>
                            <label class="flex items-center gap-2 text-sm">
                                <input v-model="tokens.chrome!.footerShowSiteName" type="checkbox" class="size-4 rounded border">
                                Show site name in footer
                            </label>
                        </div>
                        <div class="space-y-2">
                            <Label for="footer-tagline">Footer tagline</Label>
                            <Input id="footer-tagline" v-model="tokens.chrome!.footerTagline" placeholder="Optional short line under the logo" />
                        </div>
                        <div class="rounded-lg border p-3 space-y-3">
                            <label class="flex items-center gap-2 text-sm font-medium">
                                <input v-model="tokens.chrome!.footerShowCredit" type="checkbox" class="size-4 rounded border">
                                Show credit line
                            </label>
                            <div class="space-y-2">
                                <Label for="footer-credit-text">Credit text</Label>
                                <Input id="footer-credit-text" v-model="tokens.chrome!.footerCreditText" placeholder="Powered by DiamondCMS" />
                            </div>
                            <div class="space-y-2">
                                <Label for="footer-credit-url">Credit link URL</Label>
                                <Input id="footer-credit-url" v-model="tokens.chrome!.footerCreditUrl" placeholder="https://… (optional)" />
                            </div>
                        </div>
                        <div class="space-y-3 rounded-lg border p-3">
                            <div class="flex items-center justify-between gap-2">
                                <div>
                                    <p class="text-sm font-medium">Footer social links</p>
                                    <p class="text-muted-foreground text-xs">LinkedIn, Instagram, and more — shown site-wide in the footer.</p>
                                </div>
                                <Button size="sm" variant="outline" class="gap-1" @click="addFooterSocial">
                                    <Plus class="size-3.5" />
                                    Add
                                </Button>
                            </div>
                            <div class="grid gap-2 sm:grid-cols-2">
                                <button
                                    v-for="style in socialStyles"
                                    :key="`footer-${style.key}`"
                                    type="button"
                                    class="hover:border-primary rounded-lg border px-2 py-1.5 text-left text-xs transition"
                                    :class="tokens.chrome?.footerSocialStyle === style.key ? 'border-primary ring-primary/30 ring-1' : ''"
                                    @click="tokens.chrome && (tokens.chrome.footerSocialStyle = style.key)"
                                >
                                    <div class="font-medium">{{ style.label }}</div>
                                    <div class="text-muted-foreground">{{ style.description }}</div>
                                </button>
                            </div>
                            <div v-for="(item, index) in footerSocials" :key="index" class="space-y-2 rounded-lg border p-3">
                                <div class="flex items-center justify-between gap-2">
                                    <button
                                        type="button"
                                        class="hover:border-primary flex items-center gap-2 rounded-lg border px-2 py-1.5 text-xs transition"
                                        @click="openFooterIconPicker(index)"
                                    >
                                        <SocialBrandIcon :slug="item.icon" :label="item.label" :url="item.url" :size="16" />
                                        {{ item.icon || 'Pick icon' }}
                                    </button>
                                    <Button size="sm" variant="ghost" class="text-destructive h-7 px-2" @click="removeFooterSocial(index)">
                                        <Trash2 class="size-3.5" />
                                    </Button>
                                </div>
                                <Input :model-value="item.label" placeholder="Label" @update:model-value="updateFooterSocial(index, 'label', String($event))" />
                                <Input :model-value="item.url" placeholder="https://" @update:model-value="updateFooterSocial(index, 'url', String($event))" />
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>UI kit (HeroUI-inspired)</CardTitle>
                        <CardDescription>
                            Radius, surfaces, and control polish layered on shadcn-vue.
                            <a href="https://heroui.com/" class="text-primary underline-offset-2 hover:underline" target="_blank" rel="noopener">HeroUI</a>
                            itself is React-only — these tokens bring a similar soft, customizable feel without a second component stack.
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div>
                            <p class="mb-2 text-sm font-medium">Corner radius</p>
                            <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                                <button
                                    v-for="style in radiusPresets"
                                    :key="style.key"
                                    type="button"
                                    class="hover:border-primary rounded-xl border p-3 text-left transition"
                                    :class="tokens.uiKit?.radiusPreset === style.key ? 'border-primary ring-primary/30 ring-2' : ''"
                                    @click="tokens.uiKit && (tokens.uiKit.radiusPreset = style.key)"
                                >
                                    <div class="bg-primary/20 mb-2 h-8 w-full border border-dashed" :style="{ borderRadius: style.key === 'full' ? '999px' : style.key === 'xl' ? '1.15rem' : style.key === 'lg' ? '0.85rem' : style.key === 'sm' ? '0.25rem' : '0.5rem' }" />
                                    <div class="text-sm font-medium">{{ style.label }}</div>
                                    <div class="text-muted-foreground text-xs">{{ style.description }}</div>
                                </button>
                            </div>
                        </div>
                        <div>
                            <p class="mb-2 text-sm font-medium">Surface</p>
                            <div class="grid gap-2 sm:grid-cols-3">
                                <button
                                    v-for="style in surfacePresets"
                                    :key="style.key"
                                    type="button"
                                    class="hover:border-primary rounded-xl border p-3 text-left transition"
                                    :class="tokens.uiKit?.surface === style.key ? 'border-primary ring-primary/30 ring-2' : ''"
                                    @click="tokens.uiKit && (tokens.uiKit.surface = style.key)"
                                >
                                    <div class="text-sm font-medium">{{ style.label }}</div>
                                    <div class="text-muted-foreground text-xs">{{ style.description }}</div>
                                </button>
                            </div>
                        </div>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <p class="mb-2 text-sm font-medium">Density</p>
                                <div class="grid gap-2">
                                    <button
                                        v-for="style in densityPresets"
                                        :key="style.key"
                                        type="button"
                                        class="hover:border-primary rounded-xl border p-3 text-left transition"
                                        :class="tokens.uiKit?.density === style.key ? 'border-primary ring-primary/30 ring-2' : ''"
                                        @click="tokens.uiKit && (tokens.uiKit.density = style.key)"
                                    >
                                        <div class="text-sm font-medium">{{ style.label }}</div>
                                        <div class="text-muted-foreground text-xs">{{ style.description }}</div>
                                    </button>
                                </div>
                            </div>
                            <div>
                                <p class="mb-2 text-sm font-medium">Control style</p>
                                <div class="grid gap-2">
                                    <button
                                        v-for="style in controlPresets"
                                        :key="style.key"
                                        type="button"
                                        class="hover:border-primary rounded-xl border p-3 text-left transition"
                                        :class="tokens.uiKit?.controlStyle === style.key ? 'border-primary ring-primary/30 ring-2' : ''"
                                        @click="tokens.uiKit && (tokens.uiKit.controlStyle = style.key)"
                                    >
                                        <div class="text-sm font-medium">{{ style.label }}</div>
                                        <div class="text-muted-foreground text-xs">{{ style.description }}</div>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div>
                            <p class="mb-2 text-sm font-medium">Default social block style</p>
                            <div class="grid gap-2 sm:grid-cols-2">
                                <button
                                    v-for="style in socialStyles"
                                    :key="`kit-${style.key}`"
                                    type="button"
                                    class="hover:border-primary rounded-xl border p-3 text-left transition"
                                    :class="tokens.uiKit?.socialStyle === style.key ? 'border-primary ring-primary/30 ring-2' : ''"
                                    @click="tokens.uiKit && (tokens.uiKit.socialStyle = style.key)"
                                >
                                    <div class="text-sm font-medium">{{ style.label }}</div>
                                    <div class="text-muted-foreground text-xs">{{ style.description }}</div>
                                </button>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Spacing</CardTitle>
                        <CardDescription>Header / footer padding — fixes a tight top bar without editing CSS.</CardDescription>
                    </CardHeader>
                    <CardContent class="grid gap-3 sm:grid-cols-2">
                        <div class="space-y-2">
                            <Label for="header-pad-y">Top & bottom padding</Label>
                            <Input id="header-pad-y" v-model="tokens.spacing!.headerPadY" placeholder="1.35rem" />
                        </div>
                        <div class="space-y-2">
                            <Label for="header-pad-x">Side padding</Label>
                            <Input id="header-pad-x" v-model="tokens.spacing!.headerPadX" placeholder="1.5rem" />
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Button style</CardTitle>
                        <CardDescription>Applies to CTA buttons across the public site. Pick a preview below.</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                            <button
                                v-for="style in buttonStyles"
                                :key="style.key"
                                type="button"
                                class="hover:border-primary rounded-xl border p-3 text-left transition"
                                :class="tokens.buttons?.style === style.key ? 'border-primary ring-primary/30 ring-2' : ''"
                                @click="tokens.buttons && (tokens.buttons.style = style.key)"
                            >
                                <div class="mb-3 flex h-10 items-center">
                                    <span
                                        class="inline-flex items-center px-3 py-1.5 text-xs font-semibold"
                                        :class="{
                                            'bg-primary text-primary-foreground rounded-md': style.key === 'solid',
                                            'bg-primary/15 text-primary rounded-md': style.key === 'soft',
                                            'border-primary text-primary rounded-md border bg-transparent': style.key === 'outline',
                                            'bg-primary text-primary-foreground rounded-full': style.key === 'pill',
                                            'text-primary rounded-md bg-transparent': style.key === 'ghost',
                                            'text-primary border-primary border-b-2 rounded-none bg-transparent px-1': style.key === 'underline',
                                        }"
                                    >
                                        View My Work
                                    </span>
                                </div>
                                <div class="text-sm font-medium">{{ style.label }}</div>
                                <div class="text-muted-foreground text-xs">{{ style.description }}</div>
                            </button>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Logo</CardTitle>
                        <CardDescription>Defaults use the Diamond brand kit in <code>/brand</code>. Upload a custom URL anytime.</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="flex flex-wrap gap-2">
                            <button
                                v-for="mark in brandDefaults"
                                :key="mark.path"
                                type="button"
                                class="hover:border-primary rounded-lg border p-2 transition"
                                :class="tokens.branding.logo === mark.path ? 'border-primary ring-primary/30 ring-2' : ''"
                                @click="tokens.branding.logo = mark.path"
                            >
                                <img :src="mark.path" :alt="mark.label" class="h-8 w-auto">
                            </button>
                        </div>
                        <div class="space-y-2">
                            <Label for="logo-url">Custom logo URL</Label>
                            <Input id="logo-url" v-model="tokens.branding.logo" placeholder="/storage/media/..." />
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Colors & visitor theme</CardTitle>
                        <CardDescription>Set the default appearance, then allow or lock visitor light/dark switching.</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="space-y-2">
                            <Label for="mode">Default appearance</Label>
                            <select id="mode" v-model="tokens.mode" class="border-input bg-background flex h-9 w-full rounded-md border px-3 text-sm">
                                <option value="auto">Auto (system)</option>
                                <option value="light">Light</option>
                                <option value="dark">Dark</option>
                            </select>
                        </div>
                        <div class="grid gap-3 sm:grid-cols-2">
                            <label class="flex items-start gap-2 rounded-lg border p-3 text-sm">
                                <input
                                    v-model="tokens.themeControl!.allowVisitorToggle"
                                    type="checkbox"
                                    class="mt-0.5 size-4 rounded border"
                                    :disabled="tokens.themeControl?.lockMode"
                                >
                                <span>
                                    <span class="font-medium">Show light/dark toggle</span>
                                    <span class="text-muted-foreground mt-0.5 block text-xs">Visitors can switch theme on the live site.</span>
                                </span>
                            </label>
                            <label class="flex items-start gap-2 rounded-lg border p-3 text-sm">
                                <input
                                    v-model="tokens.themeControl!.lockMode"
                                    type="checkbox"
                                    class="mt-0.5 size-4 rounded border"
                                    @change="onLockChange"
                                >
                                <span>
                                    <span class="font-medium">Lock theme</span>
                                    <span class="text-muted-foreground mt-0.5 block text-xs">Force the default mode. Hides the visitor toggle.</span>
                                </span>
                            </label>
                        </div>
                        <div class="grid gap-3 sm:grid-cols-2">
                            <div v-for="key in ['background', 'foreground', 'primary', 'accent', 'surface', 'muted']" :key="key" class="space-y-2">
                                <Label class="capitalize">Light {{ key }}</Label>
                                <div class="flex gap-2">
                                    <Input v-model="tokens.colors[key]" type="color" class="h-9 w-14 p-1" />
                                    <Input v-model="tokens.colors[key]" />
                                </div>
                            </div>
                        </div>
                        <div class="grid gap-3 sm:grid-cols-2">
                            <div v-for="key in ['background', 'foreground', 'primary', 'accent', 'surface', 'muted']" :key="`dark-${key}`" class="space-y-2">
                                <Label class="capitalize">Dark {{ key }}</Label>
                                <div class="flex gap-2">
                                    <Input v-model="tokens.dark[key]" type="color" class="h-9 w-14 p-1" />
                                    <Input v-model="tokens.dark[key]" />
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Typography</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-3">
                        <div class="flex flex-wrap gap-2">
                            <Button
                                v-for="preset in fontPresets"
                                :key="preset.label"
                                size="sm"
                                variant="outline"
                                @click="applyFont(preset)"
                            >
                                {{ preset.label }}
                            </Button>
                        </div>
                        <div class="space-y-2">
                            <Label>Body font stack</Label>
                            <Input v-model="tokens.typography.body" />
                        </div>
                        <div class="space-y-2">
                            <Label>Heading font stack</Label>
                            <Input v-model="tokens.typography.heading" />
                        </div>
                    </CardContent>
                </Card>
            </div>

            <Card class="h-fit overflow-hidden xl:sticky xl:top-4 xl:max-h-[calc(100vh-2rem)] xl:overflow-auto">
                <CardHeader>
                    <CardTitle>Live preview</CardTitle>
                    <CardDescription>Wider, sticky preview closer to the real public site chrome.</CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="rounded-xl border shadow-sm transition" :style="previewStyle">
                        <div
                            class="flex items-center gap-2 border-b"
                            :style="{ padding: `${tokens.spacing?.headerPadY || '1.35rem'} ${tokens.spacing?.headerPadX || '1.5rem'}` }"
                            :class="{
                                'justify-between': tokens.chrome?.headerStyle !== 'centered',
                                'flex-col gap-2': tokens.chrome?.headerStyle === 'centered',
                            }"
                        >
                            <div class="flex items-center gap-2">
                                <img :src="tokens.branding.logo || '/brand/logo-primary-gold.svg'" alt="" class="h-7 w-auto">
                                <span class="text-sm font-semibold" :style="{ fontFamily: 'var(--preview-heading)' }">Your site</span>
                            </div>
                            <div
                                class="flex gap-3 text-xs opacity-70"
                                :class="tokens.chrome?.headerStyle === 'pill' ? 'rounded-full border px-3 py-1' : ''"
                            >
                                <span>Work</span>
                                <span>About</span>
                                <span>Projects</span>
                                <span v-if="tokens.themeControl?.allowVisitorToggle && !tokens.themeControl?.lockMode">Theme</span>
                            </div>
                        </div>
                        <div class="space-y-4 p-6">
                            <h2 class="text-3xl font-semibold" :style="{ fontFamily: 'var(--preview-heading)', color: 'var(--preview-primary)' }">
                                Theme preview
                            </h2>
                            <p class="max-w-prose text-sm opacity-80">
                                Body copy, buttons, and footer credit use the same tokens as your live site.
                                Wider preview reduces “looked fine here, wrong on the site” surprises.
                            </p>
                            <div class="flex flex-wrap gap-2">
                                <span :class="buttonPreviewClass">View My Work</span>
                                <span :class="buttonPreviewClass">Let's Talk</span>
                            </div>
                            <div class="rounded-lg p-4 text-sm" :style="{ background: 'var(--preview-surface)' }">
                                Accent chip · <span :style="{ color: 'var(--preview-accent)' }">highlight</span>
                            </div>
                        </div>
                        <div
                            class="border-t text-xs opacity-80"
                            :style="{ padding: `${tokens.spacing?.headerPadY || '1.35rem'} ${tokens.spacing?.headerPadX || '1.5rem'}` }"
                            :class="{
                                'flex flex-wrap items-center justify-between gap-3': tokens.chrome?.footerStyle === 'split',
                                'text-center': tokens.chrome?.footerStyle === 'centered',
                            }"
                        >
                            <div v-if="tokens.chrome?.footerStyle !== 'minimal'" class="mb-1 flex items-center gap-2" :class="tokens.chrome?.footerStyle === 'centered' ? 'justify-center' : ''">
                                <img
                                    v-if="tokens.chrome?.footerShowLogo"
                                    :src="tokens.branding.logo || '/brand/logo-primary-gold.svg'"
                                    alt=""
                                    class="h-5 w-auto"
                                >
                                <strong v-if="tokens.chrome?.footerShowSiteName">Your site</strong>
                            </div>
                            <div class="flex gap-3 opacity-70" :class="tokens.chrome?.footerStyle === 'centered' ? 'justify-center' : ''">
                                <span>Privacy</span>
                                <span>Contact</span>
                            </div>
                            <p v-if="tokens.chrome?.footerTagline" class="mt-1 opacity-60">{{ tokens.chrome.footerTagline }}</p>
                            <div v-if="footerSocials.length" class="mt-2 flex flex-wrap gap-2" :class="tokens.chrome?.footerStyle === 'centered' ? 'justify-center' : ''">
                                <span
                                    v-for="(item, index) in footerSocials"
                                    :key="index"
                                    class="inline-flex items-center gap-1.5 rounded-full border px-2 py-1 text-[10px]"
                                >
                                    <SocialBrandIcon :slug="item.icon" :label="item.label" :url="item.url" :size="12" />
                                    <template v-if="tokens.chrome?.footerSocialStyle !== 'icons'">{{ item.label }}</template>
                                </span>
                            </div>
                            <p v-if="tokens.chrome?.footerShowCredit !== false" class="mt-2 w-full opacity-70">
                                {{ tokens.chrome?.footerCreditText || 'Powered by DiamondCMS' }}
                            </p>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
        <IconPicker
            v-model:open="footerSocialIconOpen"
            :model-value="footerSocials[footerSocialIconIndex]?.icon || null"
            title="Footer social icon"
            @update:model-value="onFooterIconPicked"
        />
    </section>
</template>

<style scoped>
.dc-preview-btn {
    display: inline-flex;
    align-items: center;
    padding: 0.55rem 1rem;
    font-size: 0.8rem;
    font-weight: 650;
    border-radius: 0.4rem;
    border: 1px solid transparent;
}
.dc-preview-btn--solid {
    background: var(--preview-primary);
    color: var(--preview-primary-contrast);
}
.dc-preview-btn--soft {
    background: color-mix(in srgb, var(--preview-primary) 18%, transparent);
    color: var(--preview-primary);
}
.dc-preview-btn--outline {
    background: transparent;
    color: var(--preview-primary);
    border-color: var(--preview-primary);
}
.dc-preview-btn--pill {
    background: var(--preview-primary);
    color: var(--preview-primary-contrast);
    border-radius: 999px;
}
.dc-preview-btn--ghost {
    background: transparent;
    color: var(--preview-primary);
}
.dc-preview-btn--underline {
    background: transparent;
    color: var(--preview-primary);
    border-radius: 0;
    border-bottom: 2px solid var(--preview-primary);
    padding-inline: 0.15rem;
}
</style>
