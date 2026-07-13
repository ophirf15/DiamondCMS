<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import SocialBrandIcon from '@/components/builder/SocialBrandIcon.vue'
import { showActionToast } from '@/lib/actionToast'
import { resolveFooterSocialLinks, type SocialLinkRecord } from '@/lib/socialLinks'

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
        mobileNav?: string
        footerStyle: string
        footerShowLogo: boolean
        footerShowSiteName: boolean
        footerTagline: string
        footerShowCredit?: boolean
        footerCreditText?: string
        footerCreditUrl?: string
        footerSocials?: SocialLinkItem[]
        footerSocialLinkIds?: string[]
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
    portfolio?: {
        pageLayout: string
        logoStyle: string
        logoSize: string
        logoPlacement?: string
        ctaSize: string
        skillsStyle: string
        galleryPosition: string
        galleryDisplay?: string
        galleryFit?: string
        indexLayout: string
        cardFit?: string
    }
    resume?: {
        density: string
        sectionRhythm: string
        experienceStyle: string
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

type ActiveTab = 'overall' | 'portfolio' | 'resume'
type OverallSection = 'chrome' | 'look' | 'uikit'
type PortfolioPreviewMode = 'detail' | 'index'

const props = defineProps<{
    api: <T>(url: string, options?: RequestInit) => Promise<T>
}>()

const emit = defineEmits<{
    saved: [tokens: DesignTokens]
}>()

const socialLibrary = ref<SocialLinkRecord[]>([])

const tokens = ref<DesignTokens | null>(null)
const saving = ref(false)
const activeTab = ref<ActiveTab>('overall')
const overallSection = ref<OverallSection>('chrome')
const portfolioPreviewMode = ref<PortfolioPreviewMode>('detail')

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

const mobileNavModes: StylePreset[] = [
    { key: 'hamburger', label: 'Hamburger', description: 'Menu button on phones; full links on desktop' },
    { key: 'wrap', label: 'Wrap links', description: 'Keep every link visible; they wrap under the logo' },
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

const portfolioPageLayouts: StylePreset[] = [
    { key: 'classic', label: 'Classic stack', description: 'Hero → title → logos → CTA → story' },
    { key: 'split', label: 'Split media', description: 'Media column beside story' },
    { key: 'magazine', label: 'Magazine', description: 'Bold hero, icon strip, compact meta' },
    { key: 'compact', label: 'Compact', description: 'Dense header band for quick scan' },
]

const portfolioLogoStyles: StylePreset[] = [
    { key: 'chips', label: 'Chips', description: 'Icon + label in soft pills' },
    { key: 'icons', label: 'Icons only', description: 'Larger marks, SR labels' },
    { key: 'badges', label: 'Badges', description: 'Stacked mark + caption' },
    { key: 'plain', label: 'Plain row', description: 'Quiet text-forward marks' },
]

const portfolioLogoPlacements: StylePreset[] = [
    { key: 'beside-title', label: 'Beside title', description: 'Matches title height; wide logos stay readable' },
    { key: 'below', label: 'Below summary', description: 'Classic stacked chips under the intro' },
]

const portfolioSizes: StylePreset[] = [
    { key: 'sm', label: 'Small', description: '~72% of title height' },
    { key: 'md', label: 'Medium', description: '~90% of title height' },
    { key: 'lg', label: 'Large', description: 'Full title height' },
]

const portfolioSkillsStyles: StylePreset[] = [
    { key: 'chips', label: 'Chips', description: 'One skill per pill' },
    { key: 'inline', label: 'Inline text', description: 'Comma-separated line' },
    { key: 'hidden', label: 'Hidden', description: 'Hide skills row' },
]

const portfolioIndexLayouts: StylePreset[] = [
    { key: 'grid', label: 'Card grid', description: 'Thumbnail cards' },
    { key: 'list', label: 'List', description: 'Rows with small thumbs' },
    { key: 'mosaic', label: 'Mosaic', description: 'Image-forward tiles' },
]

const galleryPositions: StylePreset[] = [
    { key: 'after', label: 'After story', description: 'Gallery below case study' },
    { key: 'before', label: 'Before story', description: 'Gallery above case study' },
    { key: 'with-media', label: 'With media (split)', description: 'Alongside cover in split layout' },
]

const galleryDisplays: StylePreset[] = [
    { key: 'carousel', label: 'Carousel', description: 'Slider with arrows, dots, thumbs' },
    { key: 'grid', label: 'Grid', description: 'All framed images at once' },
]

const mediaFits: StylePreset[] = [
    { key: 'contain', label: 'Fit (no crop)', description: 'Fixed frame; small images stay unstretched' },
    { key: 'cover', label: 'Fill (crop)', description: 'Fill the frame; edges may crop' },
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

const resumeDensities: StylePreset[] = [
    { key: 'comfortable', label: 'Comfortable', description: 'Roomy resume padding' },
    { key: 'compact', label: 'Compact', description: 'Tighter print-friendly spacing' },
]

const resumeSectionRhythms: StylePreset[] = [
    { key: 'relaxed', label: 'Relaxed', description: 'More space between sections' },
    { key: 'tight', label: 'Tight', description: 'Dense section stacking' },
]

const resumeExperienceStyles: StylePreset[] = [
    { key: 'stacked', label: 'Stacked cards', description: 'Experience roles as clear blocks' },
    { key: 'compact-list', label: 'Compact list', description: 'Dense experience rows only' },
    { key: 'timeline', label: 'Timeline', description: 'Experience only — left rule with markers' },
]

const topTabs: { key: ActiveTab, label: string }[] = [
    { key: 'overall', label: 'Overall site' },
    { key: 'portfolio', label: 'Portfolio' },
    { key: 'resume', label: 'Resume' },
]

const overallSections: { key: OverallSection, label: string }[] = [
    { key: 'chrome', label: 'Chrome' },
    { key: 'look', label: 'Look & type' },
    { key: 'uikit', label: 'UI kit' },
]

const sampleSocials: SocialLinkItem[] = [
    { label: 'LinkedIn', url: '#', icon: 'linkedin' },
    { label: 'GitHub', url: '#', icon: 'github' },
    { label: 'Instagram', url: '#', icon: 'instagram' },
]

const sampleProjects = [
    { title: 'PilotBPM', thumb: 'linear-gradient(135deg, #0d5c4d, #12325a)' },
    { title: 'Atlas Analytics', thumb: 'linear-gradient(135deg, #1f2937, #4cc9f0)' },
    { title: 'Northwind Studio', thumb: 'linear-gradient(135deg, #062828, #b8ff3c)' },
]


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
            mobileNav: 'hamburger',
            footerStyle: 'branded',
            footerShowLogo: true,
            footerShowSiteName: true,
            footerTagline: '',
            footerShowCredit: true,
            footerCreditText: 'Powered by DiamondCMS',
            footerCreditUrl: '',
            footerSocials: [],
            footerSocialLinkIds: [],
            footerSocialStyle: 'icons',
        }
    } else {
        tokensValue.chrome.mobileNav ??= 'hamburger'
        tokensValue.chrome.footerShowCredit ??= true
        tokensValue.chrome.footerCreditText ??= 'Powered by DiamondCMS'
        tokensValue.chrome.footerCreditUrl ??= ''
        tokensValue.chrome.footerSocials ??= []
        tokensValue.chrome.footerSocialLinkIds ??= []
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
    if (!tokensValue.portfolio) {
        tokensValue.portfolio = {
            pageLayout: 'classic',
            logoStyle: 'chips',
            logoSize: 'lg',
            logoPlacement: 'beside-title',
            ctaSize: 'md',
            skillsStyle: 'chips',
            galleryPosition: 'after',
            galleryDisplay: 'carousel',
            galleryFit: 'contain',
            indexLayout: 'grid',
            cardFit: 'contain',
        }
    } else {
        tokensValue.portfolio.pageLayout ??= 'classic'
        tokensValue.portfolio.logoStyle ??= 'chips'
        tokensValue.portfolio.logoSize ??= 'lg'
        tokensValue.portfolio.logoPlacement ??= 'beside-title'
        tokensValue.portfolio.ctaSize ??= 'md'
        tokensValue.portfolio.skillsStyle ??= 'chips'
        tokensValue.portfolio.galleryPosition ??= 'after'
        tokensValue.portfolio.galleryDisplay ??= 'carousel'
        tokensValue.portfolio.galleryFit ??= 'contain'
        tokensValue.portfolio.indexLayout ??= 'grid'
        tokensValue.portfolio.cardFit ??= 'contain'
    }
    if (!tokensValue.resume) {
        tokensValue.resume = {
            density: 'comfortable',
            sectionRhythm: 'relaxed',
            experienceStyle: 'stacked',
        }
    } else {
        tokensValue.resume.density ??= 'comfortable'
        tokensValue.resume.sectionRhythm ??= 'relaxed'
        tokensValue.resume.experienceStyle ??= 'stacked'
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

const footerSocialLinkIds = computed({
    get(): string[] {
        return tokens.value?.chrome?.footerSocialLinkIds ?? []
    },
    set(next: string[]) {
        if (!tokens.value?.chrome) return
        tokens.value.chrome.footerSocialLinkIds = next
    },
})

const footerSocialPreview = computed(() => resolveFooterSocialLinks(
    socialLibrary.value,
    footerSocialLinkIds.value,
    tokens.value?.chrome?.footerSocials,
))

function toggleFooterSocialLink(id: string): void {
    const ids = [...footerSocialLinkIds.value]
    const index = ids.indexOf(id)
    if (index >= 0) {
        ids.splice(index, 1)
    } else {
        ids.push(id)
    }
    footerSocialLinkIds.value = ids
}

function isFooterSocialLinkSelected(id: string): boolean {
    return footerSocialLinkIds.value.includes(id)
}

const previewRadius = computed(() => {
    const key = tokens.value?.uiKit?.radiusPreset || 'md'
    if (key === 'sm') return '0.25rem'
    if (key === 'lg') return '0.85rem'
    if (key === 'xl') return '1.15rem'
    if (key === 'full') return '999px'
    return '0.5rem'
})

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
        '--preview-radius': previewRadius.value,
    } as Record<string, string>
})

const buttonPreviewClass = computed(() => {
    const style = tokens.value?.buttons?.style || 'solid'
    return `dc-preview-btn dc-preview-btn--${style}`
})

const controlPreviewClass = computed(() => {
    const style = tokens.value?.uiKit?.controlStyle || 'soft'
    return `dc-preview-control dc-preview-control--${style}`
})

const surfaceCardClass = computed(() => {
    const surface = tokens.value?.uiKit?.surface || 'soft'
    return `dc-preview-surface dc-preview-surface--${surface}`
})

const previewTitle = computed(() => {
    if (activeTab.value === 'portfolio') return 'Portfolio preview'
    if (activeTab.value === 'resume') return 'Resume preview'
    return 'Site preview'
})

const headerPadY = computed(() => {
    if (tokens.value?.uiKit?.density === 'compact') {
        return '0.65rem'
    }
    return tokens.value?.spacing?.headerPadY || '1.35rem'
})

const portfolioLogoSizePx = computed(() => {
    const size = tokens.value?.portfolio?.logoSize || 'lg'
    if (size === 'sm') return '1.15rem'
    if (size === 'md') return '1.45rem'
    return '1.75rem'
})

const portfolioCtaClass = computed(() => {
    const size = tokens.value?.portfolio?.ctaSize || 'md'
    return `dc-preview-btn dc-preview-btn--solid dc-preview-cta--${size}`
})

const resumeMockStyle = computed(() => {
    const density = tokens.value?.resume?.density || 'comfortable'
    const rhythm = tokens.value?.resume?.sectionRhythm || 'relaxed'
    return {
        gap: density === 'compact' ? '0.75rem' : '1.25rem',
        padding: density === 'compact' ? '0.85rem' : '1.25rem',
        '--resume-section-gap': rhythm === 'tight' ? '0.65rem' : '1.15rem',
    } as Record<string, string>
})

async function load(): Promise<void> {
    try {
        const [loaded, library] = await Promise.all([
            props.api<DesignTokens>('/design'),
            props.api<SocialLinkRecord[]>('/social-links').catch(() => []),
        ])
        ensureChrome(loaded)
        tokens.value = loaded
        socialLibrary.value = library
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
        emit('saved', saved)
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
                    Tune site chrome, portfolio layouts, and resume density. Preview always matches the active tab.
                </p>
            </div>
            <Button :disabled="saving" @click="save($event)">Save theme</Button>
        </div>

        <!-- Top tabs -->
        <div class="flex flex-wrap gap-2 border-b pb-3">
            <button
                v-for="tab in topTabs"
                :key="tab.key"
                type="button"
                class="hover:border-primary rounded-lg border px-3 py-1.5 text-sm font-medium transition"
                :class="activeTab === tab.key ? 'border-primary ring-primary/30 ring-2' : ''"
                @click="activeTab = tab.key"
            >
                {{ tab.label }}
            </button>
        </div>

        <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_minmax(420px,46%)]">
            <div class="space-y-4">
                <!-- Overall / sub-nav -->
                <template v-if="activeTab === 'overall'">
                    <div class="flex flex-wrap gap-2">
                        <button
                            v-for="section in overallSections"
                            :key="section.key"
                            type="button"
                            class="hover:border-primary rounded-lg border px-3 py-1.5 text-sm transition"
                            :class="overallSection === section.key ? 'border-primary ring-primary/30 ring-2' : ''"
                            @click="overallSection = section.key"
                        >
                            {{ section.label }}
                        </button>
                    </div>

                    <!-- Overall / Chrome -->
                    <template v-if="overallSection === 'chrome'">
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
                            <CardContent class="space-y-5">
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
                                <div class="space-y-2">
                                    <Label>Mobile navigation</Label>
                                    <p class="text-muted-foreground text-xs">How the header menu behaves below ~800px.</p>
                                    <div class="grid gap-3 sm:grid-cols-2">
                                        <button
                                            v-for="mode in mobileNavModes"
                                            :key="mode.key"
                                            type="button"
                                            class="hover:border-primary rounded-xl border px-3 py-2.5 text-left transition"
                                            :class="(tokens.chrome?.mobileNav || 'hamburger') === mode.key ? 'border-primary ring-primary/30 ring-2' : ''"
                                            @click="tokens.chrome && (tokens.chrome.mobileNav = mode.key)"
                                        >
                                            <div class="text-sm font-medium">{{ mode.label }}</div>
                                            <div class="text-muted-foreground text-xs">{{ mode.description }}</div>
                                        </button>
                                    </div>
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
                                <div class="space-y-3 rounded-lg border p-3">
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
                                    <div>
                                        <p class="text-sm font-medium">Footer social links</p>
                                        <p class="text-muted-foreground text-xs">
                                            Choose from your site library (Admin → Social links). Order follows your selection.
                                        </p>
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
                                    <p v-if="!socialLibrary.length" class="text-muted-foreground text-xs">
                                        No links yet. Add LinkedIn, Instagram, and more under Social links first.
                                    </p>
                                    <label
                                        v-for="link in socialLibrary"
                                        :key="link.id"
                                        class="flex cursor-pointer items-center gap-2 rounded-lg border px-3 py-2 text-sm"
                                    >
                                        <input
                                            type="checkbox"
                                            class="size-4 rounded border"
                                            :checked="isFooterSocialLinkSelected(link.id)"
                                            @change="toggleFooterSocialLink(link.id)"
                                        >
                                        <SocialBrandIcon :slug="link.icon" :label="link.label" :url="link.url" :size="16" />
                                        <span class="min-w-0 truncate">{{ link.label }}</span>
                                    </label>
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
                    </template>

                    <!-- Overall / Look & type -->
                    <template v-else-if="overallSection === 'look'">
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
                    </template>

                    <!-- Overall / UI kit -->
                    <template v-else>
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
                    </template>
                </template>

                <!-- Portfolio -->
                <template v-else-if="activeTab === 'portfolio'">
                    <Card>
                        <CardHeader>
                            <CardTitle>Portfolio pages</CardTitle>
                            <CardDescription>
                                Layout templates and sizing for project pages and the /projects index — logos, skills, CTA, and gallery placement.
                            </CardDescription>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div>
                                <p class="mb-2 text-sm font-medium">Project page layout</p>
                                <div class="grid gap-2 sm:grid-cols-2">
                                    <button
                                        v-for="style in portfolioPageLayouts"
                                        :key="style.key"
                                        type="button"
                                        class="hover:border-primary rounded-xl border p-3 text-left transition"
                                        :class="tokens.portfolio?.pageLayout === style.key ? 'border-primary ring-primary/30 ring-2' : ''"
                                        @click="tokens.portfolio && (tokens.portfolio.pageLayout = style.key)"
                                    >
                                        <div class="text-sm font-medium">{{ style.label }}</div>
                                        <div class="text-muted-foreground text-xs">{{ style.description }}</div>
                                    </button>
                                </div>
                            </div>
                            <div>
                                <p class="mb-2 text-sm font-medium">Logo placement</p>
                                <div class="grid gap-2 sm:grid-cols-2">
                                    <button
                                        v-for="style in portfolioLogoPlacements"
                                        :key="style.key"
                                        type="button"
                                        class="hover:border-primary rounded-xl border p-3 text-left transition"
                                        :class="tokens.portfolio?.logoPlacement === style.key ? 'border-primary ring-primary/30 ring-2' : ''"
                                        @click="tokens.portfolio && (tokens.portfolio.logoPlacement = style.key)"
                                    >
                                        <div class="text-sm font-medium">{{ style.label }}</div>
                                        <div class="text-muted-foreground text-xs">{{ style.description }}</div>
                                    </button>
                                </div>
                            </div>
                            <div>
                                <p class="mb-2 text-sm font-medium">Logo / icon style</p>
                                <div class="grid gap-2 sm:grid-cols-2">
                                    <button
                                        v-for="style in portfolioLogoStyles"
                                        :key="style.key"
                                        type="button"
                                        class="hover:border-primary rounded-xl border p-3 text-left transition"
                                        :class="tokens.portfolio?.logoStyle === style.key ? 'border-primary ring-primary/30 ring-2' : ''"
                                        @click="tokens.portfolio && (tokens.portfolio.logoStyle = style.key)"
                                    >
                                        <div class="text-sm font-medium">{{ style.label }}</div>
                                        <div class="text-muted-foreground text-xs">{{ style.description }}</div>
                                    </button>
                                </div>
                            </div>
                            <div class="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <p class="mb-2 text-sm font-medium">Logo size</p>
                                    <div class="grid gap-2">
                                        <button
                                            v-for="style in portfolioSizes"
                                            :key="`logo-${style.key}`"
                                            type="button"
                                            class="hover:border-primary rounded-xl border p-3 text-left transition"
                                            :class="tokens.portfolio?.logoSize === style.key ? 'border-primary ring-primary/30 ring-2' : ''"
                                            @click="tokens.portfolio && (tokens.portfolio.logoSize = style.key)"
                                        >
                                            <div class="text-sm font-medium">{{ style.label }}</div>
                                            <div class="text-muted-foreground text-xs">{{ style.description }}</div>
                                        </button>
                                    </div>
                                </div>
                                <div>
                                    <p class="mb-2 text-sm font-medium">Visit button size</p>
                                    <div class="grid gap-2">
                                        <button
                                            v-for="style in portfolioSizes"
                                            :key="`cta-${style.key}`"
                                            type="button"
                                            class="hover:border-primary rounded-xl border p-3 text-left transition"
                                            :class="tokens.portfolio?.ctaSize === style.key ? 'border-primary ring-primary/30 ring-2' : ''"
                                            @click="tokens.portfolio && (tokens.portfolio.ctaSize = style.key)"
                                        >
                                            <div class="text-sm font-medium">{{ style.label }}</div>
                                            <div class="text-muted-foreground text-xs">{{ style.description }}</div>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div>
                                <p class="mb-2 text-sm font-medium">Skills display</p>
                                <div class="grid gap-2 sm:grid-cols-3">
                                    <button
                                        v-for="style in portfolioSkillsStyles"
                                        :key="style.key"
                                        type="button"
                                        class="hover:border-primary rounded-xl border p-3 text-left transition"
                                        :class="tokens.portfolio?.skillsStyle === style.key ? 'border-primary ring-primary/30 ring-2' : ''"
                                        @click="tokens.portfolio && (tokens.portfolio.skillsStyle = style.key)"
                                    >
                                        <div class="text-sm font-medium">{{ style.label }}</div>
                                        <div class="text-muted-foreground text-xs">{{ style.description }}</div>
                                    </button>
                                </div>
                            </div>
                            <div>
                                <p class="mb-2 text-sm font-medium">Gallery placement</p>
                                <div class="grid gap-2 sm:grid-cols-3">
                                    <button
                                        v-for="style in galleryPositions"
                                        :key="style.key"
                                        type="button"
                                        class="hover:border-primary rounded-xl border p-3 text-left transition"
                                        :class="tokens.portfolio?.galleryPosition === style.key ? 'border-primary ring-primary/30 ring-2' : ''"
                                        @click="tokens.portfolio && (tokens.portfolio.galleryPosition = style.key)"
                                    >
                                        <div class="text-sm font-medium">{{ style.label }}</div>
                                        <div class="text-muted-foreground text-xs">{{ style.description }}</div>
                                    </button>
                                </div>
                            </div>
                            <div class="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <p class="mb-2 text-sm font-medium">Gallery display</p>
                                    <div class="grid gap-2">
                                        <button
                                            v-for="style in galleryDisplays"
                                            :key="style.key"
                                            type="button"
                                            class="hover:border-primary rounded-xl border p-3 text-left transition"
                                            :class="tokens.portfolio?.galleryDisplay === style.key ? 'border-primary ring-primary/30 ring-2' : ''"
                                            @click="tokens.portfolio && (tokens.portfolio.galleryDisplay = style.key)"
                                        >
                                            <div class="text-sm font-medium">{{ style.label }}</div>
                                            <div class="text-muted-foreground text-xs">{{ style.description }}</div>
                                        </button>
                                    </div>
                                </div>
                                <div>
                                    <p class="mb-2 text-sm font-medium">Image fit (gallery + cards)</p>
                                    <div class="grid gap-2">
                                        <button
                                            v-for="style in mediaFits"
                                            :key="style.key"
                                            type="button"
                                            class="hover:border-primary rounded-xl border p-3 text-left transition"
                                            :class="(tokens.portfolio?.galleryFit || tokens.portfolio?.cardFit) === style.key ? 'border-primary ring-primary/30 ring-2' : ''"
                                            @click="tokens.portfolio && (tokens.portfolio.galleryFit = style.key, tokens.portfolio.cardFit = style.key)"
                                        >
                                            <div class="text-sm font-medium">{{ style.label }}</div>
                                            <div class="text-muted-foreground text-xs">{{ style.description }}</div>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div>
                                <p class="mb-2 text-sm font-medium">Projects index</p>
                                <div class="grid gap-2 sm:grid-cols-3">
                                    <button
                                        v-for="style in portfolioIndexLayouts"
                                        :key="style.key"
                                        type="button"
                                        class="hover:border-primary rounded-xl border p-3 text-left transition"
                                        :class="tokens.portfolio?.indexLayout === style.key ? 'border-primary ring-primary/30 ring-2' : ''"
                                        @click="tokens.portfolio && (tokens.portfolio.indexLayout = style.key)"
                                    >
                                        <div class="text-sm font-medium">{{ style.label }}</div>
                                        <div class="text-muted-foreground text-xs">{{ style.description }}</div>
                                    </button>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </template>

                <!-- Resume -->
                <template v-else>
                    <Card>
                        <CardHeader>
                            <CardTitle>Resume layout</CardTitle>
                            <CardDescription>Density, section spacing, and how experience roles are presented.</CardDescription>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div>
                                <p class="mb-2 text-sm font-medium">Density</p>
                                <div class="grid gap-2 sm:grid-cols-2">
                                    <button
                                        v-for="style in resumeDensities"
                                        :key="style.key"
                                        type="button"
                                        class="hover:border-primary rounded-xl border p-3 text-left transition"
                                        :class="tokens.resume?.density === style.key ? 'border-primary ring-primary/30 ring-2' : ''"
                                        @click="tokens.resume && (tokens.resume.density = style.key)"
                                    >
                                        <div class="text-sm font-medium">{{ style.label }}</div>
                                        <div class="text-muted-foreground text-xs">{{ style.description }}</div>
                                    </button>
                                </div>
                            </div>
                            <div>
                                <p class="mb-2 text-sm font-medium">Section rhythm</p>
                                <div class="grid gap-2 sm:grid-cols-2">
                                    <button
                                        v-for="style in resumeSectionRhythms"
                                        :key="style.key"
                                        type="button"
                                        class="hover:border-primary rounded-xl border p-3 text-left transition"
                                        :class="tokens.resume?.sectionRhythm === style.key ? 'border-primary ring-primary/30 ring-2' : ''"
                                        @click="tokens.resume && (tokens.resume.sectionRhythm = style.key)"
                                    >
                                        <div class="text-sm font-medium">{{ style.label }}</div>
                                        <div class="text-muted-foreground text-xs">{{ style.description }}</div>
                                    </button>
                                </div>
                            </div>
                            <div>
                                <p class="mb-2 text-sm font-medium">Experience style</p>
                                <div class="grid gap-2 sm:grid-cols-3">
                                    <button
                                        v-for="style in resumeExperienceStyles"
                                        :key="style.key"
                                        type="button"
                                        class="hover:border-primary rounded-xl border p-3 text-left transition"
                                        :class="tokens.resume?.experienceStyle === style.key ? 'border-primary ring-primary/30 ring-2' : ''"
                                        @click="tokens.resume && (tokens.resume.experienceStyle = style.key)"
                                    >
                                        <div class="text-sm font-medium">{{ style.label }}</div>
                                        <div class="text-muted-foreground text-xs">{{ style.description }}</div>
                                    </button>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </template>
            </div>

            <!-- Preview (right sticky column) -->
            <Card class="h-fit overflow-hidden xl:sticky xl:top-4 xl:max-h-[calc(100vh-2rem)] xl:overflow-auto">
                <CardHeader>
                    <CardTitle>{{ previewTitle }}</CardTitle>
                    <CardDescription>
                        <template v-if="activeTab === 'portfolio'">
                            Toggle detail vs index — preview follows your portfolio tokens.
                        </template>
                        <template v-else-if="activeTab === 'resume'">
                            Two-column resume mock using density, rhythm, and experience style.
                        </template>
                        <template v-else>
                            Sticky site chrome mock with radius, surface, density, and socials.
                        </template>
                    </CardDescription>
                </CardHeader>
                <CardContent class="space-y-3">
                    <!-- Portfolio preview mode toggle -->
                    <div v-if="activeTab === 'portfolio'" class="flex flex-wrap gap-2">
                        <button
                            type="button"
                            class="hover:border-primary rounded-lg border px-3 py-1.5 text-xs font-medium transition"
                            :class="portfolioPreviewMode === 'detail' ? 'border-primary ring-primary/30 ring-2' : ''"
                            @click="portfolioPreviewMode = 'detail'"
                        >
                            Project detail
                        </button>
                        <button
                            type="button"
                            class="hover:border-primary rounded-lg border px-3 py-1.5 text-xs font-medium transition"
                            :class="portfolioPreviewMode === 'index' ? 'border-primary ring-primary/30 ring-2' : ''"
                            @click="portfolioPreviewMode = 'index'"
                        >
                            Projects index
                        </button>
                    </div>

                    <div class="overflow-hidden rounded-xl border shadow-sm transition" :style="previewStyle">
                        <!-- Overall / Site preview -->
                        <template v-if="activeTab === 'overall'">
                            <div
                                class="flex items-center gap-2 border-b"
                                :style="{
                                    padding: `${headerPadY} ${tokens.spacing?.headerPadX || '1.5rem'}`,
                                    borderRadius: 0,
                                }"
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
                                </p>
                                <div class="flex flex-wrap gap-2">
                                    <span :class="buttonPreviewClass">View My Work</span>
                                    <span :class="buttonPreviewClass">Let's Talk</span>
                                    <span :class="controlPreviewClass">Soft control</span>
                                </div>
                                <div
                                    :class="surfaceCardClass"
                                    class="p-4 text-sm"
                                    :style="{ background: 'var(--preview-surface)', borderRadius: 'var(--preview-radius)' }"
                                >
                                    Accent chip · <span :style="{ color: 'var(--preview-accent)' }">highlight</span>
                                </div>
                                <!-- Sample social row (uiKit.socialStyle) -->
                                <div
                                    class="text-xs"
                                    :class="{
                                        'space-y-1': tokens.uiKit?.socialStyle === 'list',
                                        'flex flex-wrap gap-2': tokens.uiKit?.socialStyle !== 'list',
                                    }"
                                >
                                    <template v-if="tokens.uiKit?.socialStyle === 'list'">
                                        <div v-for="item in sampleSocials" :key="item.label" class="opacity-80">
                                            · {{ item.label }}
                                        </div>
                                    </template>
                                    <template v-else>
                                        <span
                                            v-for="item in sampleSocials"
                                            :key="item.label"
                                            class="inline-flex items-center gap-1.5 text-[10px]"
                                            :class="{
                                                'rounded-full border px-2 py-1': tokens.uiKit?.socialStyle === 'pills' || tokens.uiKit?.socialStyle === 'icons-labels',
                                                'rounded-full border p-1.5': tokens.uiKit?.socialStyle === 'icons',
                                            }"
                                            :style="{ borderRadius: tokens.uiKit?.socialStyle === 'pills' ? '999px' : 'var(--preview-radius)' }"
                                        >
                                            <SocialBrandIcon :slug="item.icon" :label="item.label" :url="item.url" :size="12" />
                                            <template v-if="tokens.uiKit?.socialStyle === 'icons-labels' || tokens.uiKit?.socialStyle === 'pills'">
                                                {{ item.label }}
                                            </template>
                                        </span>
                                    </template>
                                </div>
                            </div>
                            <div
                                class="border-t text-xs opacity-80"
                                :style="{ padding: `${headerPadY} ${tokens.spacing?.headerPadX || '1.5rem'}` }"
                                :class="{
                                    'flex flex-wrap items-center justify-between gap-3': tokens.chrome?.footerStyle === 'split',
                                    'text-center': tokens.chrome?.footerStyle === 'centered',
                                }"
                            >
                                <div
                                    v-if="tokens.chrome?.footerStyle !== 'minimal'"
                                    class="flex items-center gap-2"
                                    :class="{
                                        'mb-1': tokens.chrome?.footerStyle !== 'split',
                                        'mr-auto': tokens.chrome?.footerStyle === 'split',
                                        'justify-center': tokens.chrome?.footerStyle === 'centered',
                                    }"
                                >
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
                                <p v-if="tokens.chrome?.footerTagline" class="mt-1 w-full opacity-60">{{ tokens.chrome.footerTagline }}</p>
                                <div
                                    v-if="footerSocialPreview.length"
                                    class="flex flex-wrap gap-2"
                                    :class="{
                                        'mt-2': tokens.chrome?.footerStyle !== 'split',
                                        'ml-auto': tokens.chrome?.footerStyle === 'split',
                                        'justify-center': tokens.chrome?.footerStyle === 'centered',
                                    }"
                                >
                                    <span
                                        v-for="(item, index) in footerSocialPreview"
                                        :key="index"
                                        class="inline-flex items-center gap-1.5 rounded-full border px-2 py-1 text-[10px]"
                                    >
                                        <SocialBrandIcon :slug="item.icon" :label="item.label" :url="item.url" :size="12" />
                                        <template v-if="tokens.chrome?.footerSocialStyle !== 'icons'">{{ item.label }}</template>
                                    </span>
                                </div>
                                <p
                                    v-if="tokens.chrome?.footerShowCredit !== false"
                                    class="w-full opacity-70"
                                    :class="tokens.chrome?.footerStyle === 'split' ? 'mt-1' : 'mt-2'"
                                >
                                    {{ tokens.chrome?.footerCreditText || 'Powered by DiamondCMS' }}
                                </p>
                            </div>
                        </template>

                        <!-- Portfolio / detail preview -->
                        <template v-else-if="activeTab === 'portfolio' && portfolioPreviewMode === 'detail'">
                            <div
                                class="space-y-3 p-5"
                                :class="`dc-preview-project dc-preview-project--${tokens.portfolio?.pageLayout || 'classic'}`"
                            >
                                <div
                                    v-if="tokens.portfolio?.pageLayout === 'magazine' || tokens.portfolio?.pageLayout === 'split'"
                                    class="h-20 w-full border"
                                    :style="{ background: 'linear-gradient(135deg, color-mix(in srgb, var(--preview-primary) 35%, transparent), var(--preview-surface))', borderRadius: 'var(--preview-radius)' }"
                                />
                                <div
                                    class="flex items-center gap-3"
                                    :class="{
                                        'flex-col items-start': tokens.portfolio?.pageLayout === 'compact',
                                    }"
                                >
                                    <div class="flex min-w-0 flex-1 flex-wrap items-center gap-2">
                                        <h2
                                            class="text-2xl font-semibold"
                                            :style="{ fontFamily: 'var(--preview-heading)', color: 'var(--preview-primary)' }"
                                        >
                                            PilotBPM
                                        </h2>
                                        <div
                                            v-if="tokens.portfolio?.logoPlacement === 'beside-title'"
                                            class="flex flex-wrap items-center gap-1.5"
                                        >
                                            <span
                                                v-for="mark in ['Vue', 'Laravel']"
                                                :key="mark"
                                                class="inline-flex items-center gap-1 text-[10px] font-medium"
                                                :class="{
                                                    'rounded-full border px-2 py-0.5': tokens.portfolio?.logoStyle === 'chips',
                                                    'rounded border px-1.5 py-0.5': tokens.portfolio?.logoStyle === 'badges',
                                                    'opacity-80': tokens.portfolio?.logoStyle === 'plain' || tokens.portfolio?.logoStyle === 'icons',
                                                }"
                                                :style="{ height: portfolioLogoSizePx, borderRadius: tokens.portfolio?.logoStyle === 'chips' ? '999px' : 'var(--preview-radius)' }"
                                            >
                                                <span
                                                    class="inline-block rounded-sm"
                                                    :style="{
                                                        width: portfolioLogoSizePx,
                                                        height: portfolioLogoSizePx,
                                                        background: 'var(--preview-accent)',
                                                        opacity: 0.85,
                                                    }"
                                                />
                                                <template v-if="tokens.portfolio?.logoStyle !== 'icons'">{{ mark }}</template>
                                            </span>
                                        </div>
                                    </div>
                                    <span :class="portfolioCtaClass">Visit site</span>
                                </div>
                                <p class="text-sm opacity-80">
                                    Workflow automation for field ops — case study summary with logos, skills, and gallery.
                                </p>
                                <div
                                    v-if="tokens.portfolio?.logoPlacement === 'below'"
                                    class="flex flex-wrap items-center gap-1.5"
                                >
                                    <span
                                        v-for="mark in ['Vue', 'Laravel', 'Postgres']"
                                        :key="mark"
                                        class="inline-flex items-center gap-1 text-[10px] font-medium"
                                        :class="{
                                            'rounded-full border px-2 py-0.5': tokens.portfolio?.logoStyle === 'chips',
                                            'rounded border px-1.5 py-0.5': tokens.portfolio?.logoStyle === 'badges',
                                            'opacity-80': tokens.portfolio?.logoStyle === 'plain' || tokens.portfolio?.logoStyle === 'icons',
                                        }"
                                        :style="{ height: portfolioLogoSizePx }"
                                    >
                                        <span
                                            class="inline-block rounded-sm"
                                            :style="{
                                                width: portfolioLogoSizePx,
                                                height: portfolioLogoSizePx,
                                                background: 'var(--preview-accent)',
                                                opacity: 0.85,
                                            }"
                                        />
                                        <template v-if="tokens.portfolio?.logoStyle !== 'icons'">{{ mark }}</template>
                                    </span>
                                </div>
                                <div v-if="tokens.portfolio?.skillsStyle !== 'hidden'" class="text-xs">
                                    <div v-if="tokens.portfolio?.skillsStyle === 'chips'" class="flex flex-wrap gap-1.5">
                                        <span
                                            v-for="skill in ['BPM', 'Vue', 'API']"
                                            :key="skill"
                                            class="rounded-full border px-2 py-0.5"
                                            :style="{ borderRadius: '999px' }"
                                        >
                                            {{ skill }}
                                        </span>
                                    </div>
                                    <p v-else class="opacity-75">BPM, Vue, API</p>
                                </div>
                                <div
                                    v-if="tokens.portfolio?.galleryPosition === 'before'"
                                    class="dc-preview-gallery"
                                    :class="tokens.portfolio?.galleryDisplay === 'grid' ? 'dc-preview-gallery--grid' : 'dc-preview-gallery--carousel'"
                                >
                                    <div
                                        v-for="n in 3"
                                        :key="n"
                                        class="border"
                                        :style="{
                                            background: `color-mix(in srgb, var(--preview-primary) ${20 + n * 15}%, var(--preview-surface))`,
                                            borderRadius: 'var(--preview-radius)',
                                            backgroundSize: tokens.portfolio?.galleryFit === 'cover' ? 'cover' : 'contain',
                                        }"
                                    />
                                </div>
                                <div
                                    v-if="tokens.portfolio?.pageLayout === 'split'"
                                    class="grid gap-3 sm:grid-cols-2"
                                >
                                    <div
                                        class="min-h-16 border"
                                        :style="{ background: 'var(--preview-surface)', borderRadius: 'var(--preview-radius)' }"
                                    />
                                    <p class="text-xs opacity-75">Split media column sits beside the story body.</p>
                                </div>
                                <p v-else class="text-xs opacity-75">
                                    Case study body — layout hint: {{ tokens.portfolio?.pageLayout || 'classic' }}.
                                </p>
                                <div
                                    v-if="tokens.portfolio?.galleryPosition !== 'before'"
                                    class="dc-preview-gallery"
                                    :class="tokens.portfolio?.galleryDisplay === 'grid' ? 'dc-preview-gallery--grid' : 'dc-preview-gallery--carousel'"
                                >
                                    <div
                                        v-for="n in 3"
                                        :key="`after-${n}`"
                                        class="border"
                                        :style="{
                                            background: `color-mix(in srgb, var(--preview-accent) ${15 + n * 18}%, var(--preview-surface))`,
                                            borderRadius: 'var(--preview-radius)',
                                        }"
                                    />
                                </div>
                            </div>
                        </template>

                        <!-- Portfolio / index preview -->
                        <template v-else-if="activeTab === 'portfolio'">
                            <div class="space-y-3 p-5">
                                <h2 class="text-lg font-semibold" :style="{ fontFamily: 'var(--preview-heading)' }">Projects</h2>
                                <div
                                    :class="{
                                        'grid gap-3 sm:grid-cols-3': tokens.portfolio?.indexLayout === 'grid',
                                        'space-y-2': tokens.portfolio?.indexLayout === 'list',
                                        'grid grid-cols-2 gap-2': tokens.portfolio?.indexLayout === 'mosaic',
                                    }"
                                >
                                    <div
                                        v-for="(project, idx) in sampleProjects"
                                        :key="project.title"
                                        class="overflow-hidden border"
                                        :class="{
                                            'flex items-center gap-3 p-2': tokens.portfolio?.indexLayout === 'list',
                                            'col-span-2': tokens.portfolio?.indexLayout === 'mosaic' && idx === 0,
                                        }"
                                        :style="{ borderRadius: 'var(--preview-radius)' }"
                                    >
                                        <div
                                            :class="{
                                                'aspect-[4/3] w-full': tokens.portfolio?.indexLayout !== 'list',
                                                'h-12 w-16 shrink-0': tokens.portfolio?.indexLayout === 'list',
                                                'aspect-[16/9]': tokens.portfolio?.indexLayout === 'mosaic' && idx === 0,
                                            }"
                                            :style="{
                                                background: project.thumb,
                                                backgroundSize: tokens.portfolio?.cardFit === 'cover' ? 'cover' : 'contain',
                                                backgroundPosition: 'center',
                                                backgroundRepeat: 'no-repeat',
                                            }"
                                        />
                                        <div :class="tokens.portfolio?.indexLayout === 'list' ? '' : 'p-2'">
                                            <div class="text-xs font-medium">{{ project.title }}</div>
                                            <div class="text-[10px] opacity-60">Case study</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>

                        <!-- Resume preview -->
                        <template v-else>
                            <div
                                class="dc-preview-resume grid gap-4 sm:grid-cols-[minmax(0,0.9fr)_minmax(0,1.4fr)]"
                                :style="resumeMockStyle"
                            >
                                <aside class="space-y-2 border-r pr-3 text-sm" :style="{ borderColor: 'color-mix(in srgb, var(--preview-muted) 35%, transparent)' }">
                                    <h2 class="text-lg font-semibold" :style="{ fontFamily: 'var(--preview-heading)', color: 'var(--preview-primary)' }">
                                        Alex Rivera
                                    </h2>
                                    <p class="text-xs opacity-75">Product engineer</p>
                                    <p class="text-[10px] opacity-60">alex@example.com</p>
                                    <span :class="buttonPreviewClass" class="!text-[10px] !px-2 !py-1">Download</span>
                                </aside>
                                <div class="min-w-0 space-y-2 text-sm" :style="{ gap: 'var(--resume-section-gap)', display: 'flex', flexDirection: 'column' }">
                                    <div>
                                        <h3 class="mb-1 text-xs font-semibold uppercase tracking-wide opacity-70">Experience</h3>
                                        <div
                                            class="dc-preview-experience"
                                            :class="`dc-preview-experience--${tokens.resume?.experienceStyle || 'stacked'}`"
                                        >
                                            <div
                                                v-for="role in [
                                                    { title: 'Senior Engineer', org: 'Diamond Labs', years: '2022 — Present' },
                                                    { title: 'Frontend Lead', org: 'Northwind', years: '2019 — 2022' },
                                                ]"
                                                :key="role.title"
                                                class="dc-preview-experience-item"
                                            >
                                                <template v-if="tokens.resume?.experienceStyle === 'compact-list'">
                                                    <p class="text-xs">
                                                        <strong>{{ role.title }}</strong>
                                                        <span class="opacity-60"> · {{ role.org }} · {{ role.years }}</span>
                                                    </p>
                                                </template>
                                                <template v-else>
                                                    <div class="text-xs font-semibold">{{ role.title }}</div>
                                                    <div class="text-[10px] opacity-70">{{ role.org }} · {{ role.years }}</div>
                                                    <p class="mt-0.5 text-[10px] opacity-60">Shipped design system and portfolio CMS.</p>
                                                </template>
                                            </div>
                                        </div>
                                    </div>
                                    <div>
                                        <h3 class="mb-1 text-xs font-semibold uppercase tracking-wide opacity-70">Skills</h3>
                                        <p class="text-[10px] opacity-75">Vue · Laravel · Design systems</p>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </CardContent>
            </Card>
        </div>

    </section>
</template>

<style scoped>
.dc-preview-btn {
    display: inline-flex;
    align-items: center;
    padding: 0.55rem 1rem;
    font-size: 0.8rem;
    font-weight: 650;
    border-radius: var(--preview-radius, 0.4rem);
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
.dc-preview-cta--sm {
    padding: 0.35rem 0.7rem;
    font-size: 0.7rem;
}
.dc-preview-cta--md {
    padding: 0.5rem 0.9rem;
    font-size: 0.78rem;
}
.dc-preview-cta--lg {
    padding: 0.65rem 1.15rem;
    font-size: 0.88rem;
}
.dc-preview-control {
    display: inline-flex;
    align-items: center;
    padding: 0.4rem 0.85rem;
    font-size: 0.72rem;
    font-weight: 600;
    border-radius: var(--preview-radius, 0.4rem);
}
.dc-preview-control--solid {
    background: var(--preview-primary);
    color: var(--preview-primary-contrast);
}
.dc-preview-control--soft {
    background: color-mix(in srgb, var(--preview-primary) 16%, transparent);
    color: var(--preview-primary);
}
.dc-preview-control--bordered {
    background: transparent;
    color: var(--preview-primary);
    border: 1px solid var(--preview-primary);
}
.dc-preview-surface--flat {
    box-shadow: none;
}
.dc-preview-surface--soft {
    box-shadow: 0 1px 2px color-mix(in srgb, var(--preview-muted) 25%, transparent);
    backdrop-filter: blur(4px);
}
.dc-preview-surface--elevated {
    box-shadow:
        0 8px 24px color-mix(in srgb, #000 28%, transparent),
        0 1px 0 color-mix(in srgb, var(--preview-muted) 20%, transparent);
}
.dc-preview-project--compact {
    gap: 0.65rem;
}
.dc-preview-project--magazine h2 {
    font-size: 1.75rem;
    letter-spacing: -0.02em;
}
.dc-preview-gallery {
    display: grid;
    gap: 0.4rem;
}
.dc-preview-gallery--carousel {
    grid-template-columns: 1.4fr 0.7fr 0.7fr;
}
.dc-preview-gallery--carousel > div {
    min-height: 3.25rem;
}
.dc-preview-gallery--grid {
    grid-template-columns: repeat(3, minmax(0, 1fr));
}
.dc-preview-gallery--grid > div {
    min-height: 2.75rem;
}
.dc-preview-experience--stacked .dc-preview-experience-item {
    margin-bottom: 0.65rem;
    padding: 0.55rem 0.65rem;
    border: 1px solid color-mix(in srgb, var(--preview-muted) 30%, transparent);
    border-radius: var(--preview-radius, 0.4rem);
    background: color-mix(in srgb, var(--preview-surface) 80%, transparent);
}
.dc-preview-experience--compact-list .dc-preview-experience-item {
    margin-bottom: 0.25rem;
    padding-block: 0.15rem;
}
.dc-preview-experience--timeline {
    border-left: 2px solid color-mix(in srgb, var(--preview-primary) 55%, transparent);
    padding-left: 0.75rem;
}
.dc-preview-experience--timeline .dc-preview-experience-item {
    position: relative;
    margin-bottom: 0.75rem;
}
.dc-preview-experience--timeline .dc-preview-experience-item::before {
    content: '';
    position: absolute;
    left: -0.95rem;
    top: 0.35rem;
    width: 0.45rem;
    height: 0.45rem;
    border-radius: 999px;
    background: var(--preview-primary);
}
</style>
