<script setup lang="ts">
import { computed, ref } from 'vue'
import { Eye, Plus } from '@lucide/vue'
import BuilderBlockView, { type BuilderBlock } from '@/components/builder/BuilderBlockView.vue'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { showActionToast } from '@/lib/actionToast'

type BuilderDocument = {
    schema: number
    title: string
    meta?: {
        shell?: string
        preview_theme?: string
        blurb?: string
    }
    blocks: BuilderBlock[]
}

type TemplateRow = {
    id: number
    name: string
    slug: string
    category: string
    builder_json: string | BuilderDocument
}

const props = defineProps<{
    templates: TemplateRow[]
    api: <T>(url: string, options?: RequestInit) => Promise<T>
}>()

const emit = defineEmits<{
    seeded: []
    use: [template: TemplateRow, event?: Event]
}>()

const previewTemplate = ref<TemplateRow | null>(null)

const previewDocument = computed<BuilderDocument | null>(() => {
    if (!previewTemplate.value) return null
    return parseDocument(previewTemplate.value)
})

const pageTemplates = computed(() => props.templates.filter((row) => row.category === 'page'))
const resumeTemplates = computed(() => props.templates.filter((row) => row.category === 'resume'))
const otherTemplates = computed(() => props.templates.filter((row) => !['page', 'resume'].includes(row.category)))

function parseDocument(template: TemplateRow): BuilderDocument {
    return typeof template.builder_json === 'string'
        ? JSON.parse(template.builder_json) as BuilderDocument
        : template.builder_json
}

function blurb(template: TemplateRow): string {
    const doc = parseDocument(template)
    return doc.meta?.blurb || 'Starter layout you can customize visually.'
}

function previewTheme(template: TemplateRow): string {
    return parseDocument(template).meta?.preview_theme || 'light'
}

function shell(template: TemplateRow): string {
    return parseDocument(template).meta?.shell || 'default'
}

function parseBlocks(template: TemplateRow): BuilderBlock[] {
    return parseDocument(template).blocks ?? []
}

function themeClass(theme: string): string {
    return `dc-tpl-theme dc-tpl-theme--${theme}`
}

async function seed(event?: Event): Promise<void> {
    await props.api('/templates/seed', { method: 'POST', body: '{}' })
    emit('seeded')
    showActionToast(event, 'Starter templates ready')
}
</script>

<template>
    <section class="space-y-6">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <h1 class="text-3xl font-semibold tracking-tight">Templates</h1>
                <p class="text-muted-foreground text-sm">Polished starters for home, about, projects, and résumés — with accurate live previews.</p>
            </div>
            <Button variant="outline" @click="seed($event)">Refresh starter set</Button>
        </div>

        <div v-if="templates.length === 0" class="rounded-xl border border-dashed p-10 text-center">
            <p class="text-muted-foreground mb-4 text-sm">No templates installed yet.</p>
            <Button @click="seed($event)">Install starter templates</Button>
        </div>

        <template v-else>
            <div v-if="pageTemplates.length" class="space-y-3">
                <h2 class="text-lg font-semibold tracking-tight">Pages</h2>
                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    <Card v-for="template in pageTemplates" :key="template.id" class="overflow-hidden">
                        <div class="border-b p-3" :class="themeClass(previewTheme(template))">
                            <div class="dc-tpl-thumb">
                                <div
                                    class="dc-tpl-thumb-scaler pointer-events-none"
                                    :class="{ 'dc-tpl-sidebar': shell(template) === 'sidebar-dark' }"
                                >
                                    <div v-if="shell(template) === 'sidebar-dark'" class="dc-tpl-rail">
                                        <div class="dc-tpl-rail-photo" />
                                        <div class="dc-tpl-rail-name">{{ template.name.split(' ')[0] }}</div>
                                        <div class="dc-tpl-rail-links">
                                            <span>Home</span>
                                            <span>About</span>
                                            <span>Projects</span>
                                        </div>
                                    </div>
                                    <div class="dc-tpl-body space-y-2">
                                        <BuilderBlockView
                                            v-for="block in parseBlocks(template).slice(0, 8)"
                                            :key="block.id"
                                            :block="block"
                                            :selected-id="null"
                                            :readonly="true"
                                            :preview-mode="true"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>
                        <CardHeader class="space-y-2">
                            <div class="flex items-center justify-between gap-2">
                                <CardTitle class="text-base">{{ template.name }}</CardTitle>
                                <Badge variant="secondary">{{ template.category }}</Badge>
                            </div>
                            <CardDescription>{{ blurb(template) }}</CardDescription>
                        </CardHeader>
                        <CardContent class="flex gap-2">
                            <Button variant="outline" class="flex-1 gap-2" @click="previewTemplate = template">
                                <Eye class="size-4" />
                                <span>Preview</span>
                            </Button>
                            <Button class="flex-1 gap-2" @click="emit('use', template, $event)">
                                <Plus class="size-4" />
                                <span>Use</span>
                            </Button>
                        </CardContent>
                    </Card>
                </div>
            </div>

            <div v-if="resumeTemplates.length" class="space-y-3">
                <h2 class="text-lg font-semibold tracking-tight">Résumés</h2>
                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    <Card v-for="template in resumeTemplates" :key="template.id" class="overflow-hidden">
                        <div class="border-b p-3" :class="themeClass(previewTheme(template))">
                            <div class="dc-tpl-thumb">
                                <div class="dc-tpl-thumb-scaler pointer-events-none">
                                    <div class="dc-tpl-body space-y-2">
                                        <BuilderBlockView
                                            v-for="block in parseBlocks(template).slice(0, 8)"
                                            :key="block.id"
                                            :block="block"
                                            :selected-id="null"
                                            :readonly="true"
                                            :preview-mode="true"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>
                        <CardHeader class="space-y-2">
                            <div class="flex items-center justify-between gap-2">
                                <CardTitle class="text-base">{{ template.name }}</CardTitle>
                                <Badge variant="secondary">{{ template.category }}</Badge>
                            </div>
                            <CardDescription>{{ blurb(template) }}</CardDescription>
                        </CardHeader>
                        <CardContent class="flex gap-2">
                            <Button variant="outline" class="flex-1 gap-2" @click="previewTemplate = template">
                                <Eye class="size-4" />
                                <span>Preview</span>
                            </Button>
                            <Button class="flex-1 gap-2" @click="emit('use', template, $event)">
                                <Plus class="size-4" />
                                <span>Use</span>
                            </Button>
                        </CardContent>
                    </Card>
                </div>
            </div>

            <div v-if="otherTemplates.length" class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                <Card v-for="template in otherTemplates" :key="template.id" class="overflow-hidden">
                    <CardHeader>
                        <CardTitle>{{ template.name }}</CardTitle>
                        <CardDescription>{{ blurb(template) }}</CardDescription>
                    </CardHeader>
                    <CardContent class="flex gap-2">
                        <Button variant="outline" class="flex-1" @click="previewTemplate = template">Preview</Button>
                        <Button class="flex-1" @click="emit('use', template, $event)">Use</Button>
                    </CardContent>
                </Card>
            </div>
        </template>

        <div
            v-if="previewTemplate && previewDocument"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
            @click.self="previewTemplate = null"
        >
            <Card class="flex max-h-[92vh] w-full max-w-5xl flex-col overflow-hidden shadow-xl">
                <CardHeader class="flex-row items-start justify-between gap-3 space-y-0">
                    <div>
                        <CardTitle>{{ previewTemplate.name }}</CardTitle>
                        <CardDescription>{{ blurb(previewTemplate) }}</CardDescription>
                    </div>
                    <div class="flex gap-2">
                        <Button variant="outline" @click="previewTemplate = null">Close</Button>
                        <Button @click="emit('use', previewTemplate, $event); previewTemplate = null">Use this template</Button>
                    </div>
                </CardHeader>
                <CardContent class="overflow-auto">
                    <div
                        class="rounded-xl border p-5"
                        :class="themeClass(previewTheme(previewTemplate))"
                    >
                        <div
                            class="dc-tpl-canvas p-2"
                            :class="{ 'dc-tpl-sidebar': shell(previewTemplate) === 'sidebar-dark' }"
                        >
                            <div v-if="shell(previewTemplate) === 'sidebar-dark'" class="dc-tpl-rail">
                                <div class="dc-tpl-rail-photo" />
                                <div class="dc-tpl-rail-name">Profile</div>
                                <div class="dc-tpl-rail-links">
                                    <span>Home</span>
                                    <span>About</span>
                                    <span>Projects</span>
                                    <span>Contact</span>
                                </div>
                            </div>
                            <div class="dc-tpl-body space-y-3">
                                <BuilderBlockView
                                    v-for="block in previewDocument.blocks"
                                    :key="block.id"
                                    :block="block"
                                    :selected-id="null"
                                    :readonly="true"
                                    :preview-mode="true"
                                />
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </section>
</template>

<style scoped>
.dc-tpl-thumb {
    height: 220px;
    overflow: hidden;
    position: relative;
    border-radius: 0.5rem;
    border: 1px solid rgb(255 255 255 / 12%);
}

.dc-tpl-thumb-scaler {
    display: grid;
    gap: 0.75rem;
    min-height: 640px;
    padding: 1rem;
    transform: scale(0.36);
    transform-origin: top left;
    width: 900px;
}

.dc-tpl-thumb-scaler.dc-tpl-sidebar {
    grid-template-columns: 88px minmax(0, 1fr);
}

.dc-tpl-theme {
    color: #14201c;
}

.dc-tpl-theme--light {
    background: linear-gradient(180deg, #f7f8fa, #eef1f5);
    color: #12141a;
}

.dc-tpl-theme--dark-teal,
.dc-tpl-theme--dark-gallery {
    background: #0f0f0f;
    color: #f2f2f2;
}

.dc-tpl-theme--dark-navy {
    background: radial-gradient(circle at 20% 20%, #12325a 0%, #050a15 55%);
    color: #eef5ff;
}

.dc-tpl-theme--split-teal {
    background: linear-gradient(90deg, #0b3d3d 0 48%, #2a2a2a 48% 100%);
    color: #f4fff8;
}

.dc-tpl-theme--dark-neon {
    background: #050505;
    color: #f4fff4;
}

.dc-tpl-canvas :deep(.dc-button),
.dc-tpl-thumb-scaler :deep(.dc-button) {
    background: #2dd4bf;
    color: #042f2e;
}

.dc-tpl-theme--light :deep(.dc-button) {
    background: #0d5c4d;
    color: #f4faf7;
}

.dc-tpl-theme--dark-navy :deep(.dc-button) {
    background: #00a3ff;
    color: #041018;
}

.dc-tpl-theme--dark-neon :deep(.dc-button) {
    background: #b8ff3c;
    color: #041004;
}

.dc-tpl-sidebar {
    display: grid;
    gap: 0.75rem;
    grid-template-columns: 88px 1fr;
}

.dc-tpl-rail {
    border-right: 1px solid rgb(255 255 255 / 12%);
    display: grid;
    gap: 0.55rem;
    padding-right: 0.5rem;
}

.dc-tpl-rail-photo {
    aspect-ratio: 1;
    background: linear-gradient(145deg, #2dd4bf44, #111);
    border-radius: 0.5rem;
}

.dc-tpl-rail-name {
    font-size: 0.65rem;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
}

.dc-tpl-rail-links {
    display: grid;
    font-size: 0.55rem;
    gap: 0.25rem;
    letter-spacing: 0.1em;
    opacity: 0.75;
    text-transform: uppercase;
}

.dc-tpl-body {
    min-width: 0;
}
</style>
