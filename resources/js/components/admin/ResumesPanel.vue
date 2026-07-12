<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { ArrowDown, ArrowUp, ExternalLink, Plus, Share2, Trash2, Upload, X } from '@lucide/vue'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import { Badge } from '@/components/ui/badge'
import MediaPanel from '@/components/admin/MediaPanel.vue'
import { toast } from 'vue-sonner'

type ResumeProfile = {
    id: number
    name: string
    headline?: string | null
    email?: string | null
    phone?: string | null
    location?: string | null
    website?: string | null
    summary?: string | null
    links?: Array<{ label: string, url: string }> | string | null
}

type ProfileLink = { label: string, url: string }

type ResumeSection = {
    id?: number
    type: string
    title?: string | null
    organization?: string | null
    location?: string | null
    date?: string | null
    bullets?: string[] | string
    metadata?: Record<string, unknown>
}

type ResumeVariant = {
    id: number
    name: string
    slug: string
    visibility: string
    download_pdf?: string | null
    download_docx?: string | null
}

type MediaItem = {
    id: number
    path: string
    original_name?: string
    mime?: string
}

type ImportPayload = {
    name?: string
    headline?: string | null
    summary?: string | null
    email?: string | null
    phone?: string | null
    location?: string | null
    sections?: ResumeSection[]
}

type ResumeImport = {
    id: number
    status: string
    parsed_payload: ImportPayload | string
    extracted_text?: string
}

const SECTION_TYPES = ['experience', 'education', 'skills', 'project', 'award', 'certification', 'other'] as const

const props = defineProps<{
    api: <T>(url: string, options?: RequestInit) => Promise<T>
}>()

const profiles = ref<ResumeProfile[]>([])
const selected = ref<ResumeProfile | null>(null)
const profileLinks = ref<ProfileLink[]>([])
const sections = ref<ResumeSection[]>([])
const variants = ref<ResumeVariant[]>([])
const saving = ref(false)
const importing = ref(false)
const approving = ref(false)
const newVariantName = ref('Public résumé')
const shareUrl = ref('')
const fileInput = ref<HTMLInputElement | null>(null)
const reviewImport = ref<ResumeImport | null>(null)
const reviewPayload = ref<ImportPayload | null>(null)
const showMediaPicker = ref(false)
const mediaFiles = ref<MediaItem[]>([])
const mediaTarget = ref<{ variantId: number, field: 'download_pdf' | 'download_docx' } | null>(null)

const csrf = computed(() => document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '')

function sectionTitleLabel(type: string): string {
    return matchLabel(type, {
        experience: 'Title / role',
        education: 'Degree or program',
        skills: 'Skill group (optional)',
        project: 'Project name',
        award: 'Award title',
        certification: 'Certification title',
        other: 'Title',
    })
}

function sectionOrgLabel(type: string): string {
    return matchLabel(type, {
        experience: 'Company / organization',
        education: 'School',
        project: 'Client / org (optional)',
        award: 'Issuer (e.g. Woodmont)',
        certification: 'Issuer (e.g. CompTIA)',
        other: 'Organization (optional)',
    })
}

function showsOrganization(type: string): boolean {
    return type !== 'skills'
}

function showsDate(type: string): boolean {
    return ['experience', 'education', 'award', 'certification', 'project', 'other'].includes(type)
}

function awardLike(type: string): boolean {
    return type === 'award' || type === 'certification'
}

function bulletsPlaceholder(type: string): string {
    if (type === 'skills') return 'One skill per line (commas also split into chips)'
    if (awardLike(type)) return 'Optional details (one per line)'
    if (type === 'project') return 'One bullet per line (outcomes, stack, links)'
    return 'One bullet per line'
}

function matchLabel(type: string, map: Record<string, string>): string {
    return map[type] || 'Title'
}

function defaultSection(type: string): ResumeSection {
    if (type === 'skills') {
        return { type, title: '', organization: '', date: '', bullets: ['TypeScript', 'Laravel'] }
    }
    if (type === 'education') {
        return { type, title: 'B.S. Computer Science', organization: 'University', date: '2018 – 2022', bullets: [] }
    }
    if (type === 'award') {
        return { type, title: 'Award title', organization: 'Woodmont', date: '2024', bullets: [] }
    }
    if (type === 'certification') {
        return { type, title: 'Certification title', organization: 'Issuer', date: '2024', bullets: [] }
    }
    if (type === 'project') {
        return { type, title: 'Project name', organization: '', date: '', bullets: ['Outcome'] }
    }
    return { type, title: 'Role', organization: 'Company', date: '', bullets: ['Achievement'] }
}

async function load(): Promise<void> {
    profiles.value = await props.api<ResumeProfile[]>('/resumes')
}

async function createProfile(): Promise<void> {
    const profile = await props.api<ResumeProfile>('/resumes', {
        method: 'POST',
        body: JSON.stringify({ name: 'Primary résumé', headline: '', summary: '' }),
    })
    profiles.value.unshift(profile)
    await openProfile(profile)
    toast.success('Résumé profile created')
}

async function openProfile(profile: ResumeProfile): Promise<void> {
    selected.value = { ...profile }
    profileLinks.value = parseLinks(profile.links)
    shareUrl.value = ''
    const rows = await props.api<Array<ResumeSection & { bullets: string, metadata?: string }>>(`/resumes/${profile.id}/sections`)
    sections.value = rows.map((row) => {
        const meta = typeof row.metadata === 'string'
            ? (JSON.parse(row.metadata || '{}') as Record<string, unknown>)
            : (row.metadata || {})
        return {
            ...row,
            date: typeof meta.date === 'string' ? meta.date : '',
            bullets: typeof row.bullets === 'string' ? (JSON.parse(row.bullets || '[]') as string[]) : (row.bullets ?? []),
        }
    })
    variants.value = await props.api<ResumeVariant[]>(`/resumes/${profile.id}/variants`)
}

function parseLinks(raw: ResumeProfile['links']): ProfileLink[] {
    if (!raw) return []
    const rows = typeof raw === 'string' ? (JSON.parse(raw || '[]') as ProfileLink[]) : raw
    if (!Array.isArray(rows)) return []
    return rows.map((row) => ({
        label: String(row?.label || ''),
        url: String(row?.url || ''),
    }))
}

function addProfileLink(): void {
    profileLinks.value = [...profileLinks.value, { label: 'GitHub', url: 'https://github.com/' }]
}

function removeProfileLink(index: number): void {
    profileLinks.value = profileLinks.value.filter((_, i) => i !== index)
}

async function deleteProfile(): Promise<void> {
    if (!selected.value) return
    if (!window.confirm(`Delete “${selected.value.name}” and all its sections/variants? This cannot be undone.`)) return
    try {
        await props.api(`/resumes/${selected.value.id}`, { method: 'DELETE' })
        selected.value = null
        profileLinks.value = []
        sections.value = []
        variants.value = []
        await load()
        toast.success('Résumé deleted')
    } catch (error) {
        toast.error(error instanceof Error ? error.message : 'Could not delete résumé')
    }
}

function addSection(type = 'experience'): void {
    sections.value.push(defaultSection(type))
}

function removeSection(index: number): void {
    sections.value.splice(index, 1)
}

function moveSection(index: number, delta: number): void {
    const next = index + delta
    if (next < 0 || next >= sections.value.length) return
    const copy = [...sections.value]
    const [row] = copy.splice(index, 1)
    copy.splice(next, 0, row)
    sections.value = copy
}

function bulletsText(section: ResumeSection): string {
    return Array.isArray(section.bullets) ? section.bullets.join('\n') : String(section.bullets ?? '')
}

function setBullets(section: ResumeSection, value: string): void {
    section.bullets = value.split('\n').map((line) => line.trim()).filter(Boolean)
}

async function save(): Promise<void> {
    if (!selected.value) return
    saving.value = true
    try {
        const saved = await props.api<ResumeProfile>(`/resumes/${selected.value.id}`, {
            method: 'PUT',
            body: JSON.stringify({
                name: selected.value.name,
                headline: selected.value.headline ?? null,
                summary: selected.value.summary ?? null,
                email: selected.value.email ?? null,
                phone: selected.value.phone ?? null,
                location: selected.value.location ?? null,
                website: selected.value.website ?? null,
                links: profileLinks.value
                    .map((link) => ({ label: link.label.trim(), url: link.url.trim() }))
                    .filter((link) => link.url !== ''),
            }),
        })
        selected.value = { ...saved }
        profileLinks.value = parseLinks(saved.links)
        await props.api(`/resumes/${selected.value.id}/sections`, {
            method: 'PUT',
            body: JSON.stringify({
                sections: sections.value.map((section) => ({
                    type: section.type,
                    title: section.title,
                    organization: showsOrganization(section.type) ? section.organization : null,
                    location: section.location || null,
                    date: showsDate(section.type) ? (section.date || null) : null,
                    bullets: Array.isArray(section.bullets) ? section.bullets : [],
                })),
            }),
        })
        await load()
        toast.success('Résumé saved')
    } catch (error) {
        toast.error(error instanceof Error ? error.message : 'Could not save résumé')
    } finally {
        saving.value = false
    }
}

async function publishVariant(): Promise<void> {
    if (!selected.value) return
    try {
        const variant = await props.api<ResumeVariant>(`/resumes/${selected.value.id}/variants`, {
            method: 'POST',
            body: JSON.stringify({ name: newVariantName.value || 'Public résumé', visibility: 'public' }),
        })
        variants.value.unshift(variant)
        toast.success(`Public at /resume/${variant.slug}`)
    } catch (error) {
        toast.error(error instanceof Error ? error.message : 'Could not create variant')
    }
}

async function setVisibility(variant: ResumeVariant, visibility: string): Promise<void> {
    await props.api(`/resume-variants/${variant.id}`, {
        method: 'PUT',
        body: JSON.stringify({ visibility }),
    })
    if (selected.value) await openProfile(selected.value)
}

async function saveVariantDetails(variant: ResumeVariant): Promise<void> {
    const slug = variant.slug.trim().toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '')
    if (!slug) {
        toast.error('Slug needs at least one letter or number')
        return
    }
    try {
        const saved = await props.api<ResumeVariant>(`/resume-variants/${variant.id}`, {
            method: 'PUT',
            body: JSON.stringify({
                name: variant.name.trim() || 'Public résumé',
                slug,
                download_pdf: variant.download_pdf || null,
                download_docx: variant.download_docx || null,
            }),
        })
        const index = variants.value.findIndex((row) => row.id === variant.id)
        if (index >= 0) variants.value[index] = { ...variants.value[index], ...saved }
        toast.success('Variant saved')
    } catch (error) {
        toast.error(error instanceof Error ? error.message : 'Could not save variant')
    }
}

async function saveVariantDownloads(variant: ResumeVariant): Promise<void> {
    await saveVariantDetails(variant)
}

async function deleteVariant(variant: ResumeVariant): Promise<void> {
    if (!window.confirm(`Delete variant “${variant.name}”?`)) return
    await props.api(`/resume-variants/${variant.id}`, { method: 'DELETE' })
    if (selected.value) await openProfile(selected.value)
    toast.success('Variant deleted')
}

async function shareVariant(variant: ResumeVariant): Promise<void> {
    const result = await props.api<{ url: string }>(`/resume-variants/${variant.id}/share`, {
        method: 'POST',
        body: '{}',
    })
    shareUrl.value = result.url
    try {
        await navigator.clipboard.writeText(result.url)
        toast.success('Share link copied')
    } catch {
        toast.success('Share link created')
    }
}

function mediaUrl(item: MediaItem): string {
    return '/storage/' + String(item.path || '').replace(/^\/?storage\//, '').replace(/^\//, '')
}

async function loadMedia(): Promise<void> {
    const result = await props.api<{ data: MediaItem[] }>('/media')
    mediaFiles.value = result.data
}

async function openDownloadPicker(variant: ResumeVariant, field: 'download_pdf' | 'download_docx'): Promise<void> {
    mediaTarget.value = { variantId: variant.id, field }
    if (!mediaFiles.value.length) await loadMedia()
    showMediaPicker.value = true
}

async function pickMedia(item: MediaItem): Promise<void> {
    if (!mediaTarget.value) return
    const url = mediaUrl(item)
    const variant = variants.value.find((row) => row.id === mediaTarget.value?.variantId)
    if (!variant) return
    variant[mediaTarget.value.field] = url
    showMediaPicker.value = false
    mediaTarget.value = null
    await saveVariantDownloads(variant)
}

function triggerImport(): void {
    fileInput.value?.click()
}

async function onImportFile(event: Event): Promise<void> {
    const input = event.target as HTMLInputElement
    const file = input.files?.[0]
    if (!file) return

    importing.value = true
    try {
        const body = new FormData()
        body.append('file', file)
        if (selected.value?.id) {
            body.append('resume_profile_id', String(selected.value.id))
        }

        const response = await fetch('/admin/api/resumes/import', {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'X-CSRF-TOKEN': csrf.value,
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
            body,
        })
        if (!response.ok) {
            throw new Error((await response.json().catch(() => null))?.message || 'Import failed')
        }
        const row = await response.json() as ResumeImport
        const payload = typeof row.parsed_payload === 'string'
            ? JSON.parse(row.parsed_payload || '{}') as ImportPayload
            : (row.parsed_payload || {})
        reviewImport.value = row
        reviewPayload.value = {
            name: payload.name || '',
            headline: payload.headline || '',
            summary: payload.summary || '',
            email: payload.email || '',
            phone: payload.phone || '',
            location: payload.location || '',
            sections: (payload.sections || []).map((section) => ({
                ...section,
                bullets: Array.isArray(section.bullets) ? section.bullets : [],
            })),
        }
        toast.success('Parsed — review before approving')
    } catch (error) {
        toast.error(error instanceof Error ? error.message : 'Could not import résumé')
    } finally {
        importing.value = false
        input.value = ''
    }
}

function addReviewSection(): void {
    if (!reviewPayload.value) return
    reviewPayload.value.sections = reviewPayload.value.sections || []
    reviewPayload.value.sections.push(defaultSection('experience'))
}

function removeReviewSection(index: number): void {
    reviewPayload.value?.sections?.splice(index, 1)
}

async function approveImport(): Promise<void> {
    if (!reviewImport.value || !reviewPayload.value) return
    approving.value = true
    try {
        await props.api(`/resumes/import/${reviewImport.value.id}`, {
            method: 'PUT',
            body: JSON.stringify(reviewPayload.value),
        })
        const result = await props.api<{ resume_profile_id: number }>(`/resumes/import/${reviewImport.value.id}/approve`, {
            method: 'POST',
            body: '{}',
        })
        await load()
        const profile = profiles.value.find((row) => row.id === result.resume_profile_id)
        if (profile) await openProfile(profile)
        reviewImport.value = null
        reviewPayload.value = null
        toast.success('Import approved — profile updated')
    } catch (error) {
        toast.error(error instanceof Error ? error.message : 'Could not approve import')
    } finally {
        approving.value = false
    }
}

onMounted(load)
</script>

<template>
    <section class="space-y-4">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <h1 class="text-3xl font-semibold tracking-tight">Resumes</h1>
                <p class="text-muted-foreground text-sm">
                    Edit sections, attach PDF/DOCX downloads, and publish public variants. Awards belong as multiple entries under one Awards group — not separate page sections.
                </p>
            </div>
            <div class="flex flex-wrap gap-2">
                <input
                    ref="fileInput"
                    type="file"
                    class="hidden"
                    accept=".pdf,.docx,.txt,application/pdf,text/plain"
                    @change="onImportFile"
                >
                <Button variant="outline" class="gap-2" :disabled="importing" @click="triggerImport">
                    <Upload class="size-4" />
                    <span>{{ importing ? 'Importing…' : 'Import resume' }}</span>
                </Button>
                <Button class="gap-2" @click="createProfile"><Plus class="size-4" /><span>New profile</span></Button>
            </div>
        </div>

        <Card v-if="reviewPayload && reviewImport" class="border-primary/40">
            <CardHeader>
                <CardTitle>Review import #{{ reviewImport.id }}</CardTitle>
                <CardDescription>Edit the parsed fields, then approve to write into a résumé profile.</CardDescription>
            </CardHeader>
            <CardContent class="space-y-4">
                <div class="grid gap-3 sm:grid-cols-2">
                    <div class="space-y-2"><Label>Name</Label><Input v-model="reviewPayload.name" /></div>
                    <div class="space-y-2"><Label>Headline</Label><Input v-model="reviewPayload.headline" /></div>
                    <div class="space-y-2"><Label>Email</Label><Input v-model="reviewPayload.email" /></div>
                    <div class="space-y-2"><Label>Phone</Label><Input v-model="reviewPayload.phone" /></div>
                    <div class="space-y-2 sm:col-span-2"><Label>Summary</Label><Textarea v-model="reviewPayload.summary" rows="3" /></div>
                </div>
                <div class="flex items-center justify-between gap-2">
                    <h3 class="font-medium">Sections</h3>
                    <Button size="sm" variant="outline" @click="addReviewSection">Add section</Button>
                </div>
                <div v-for="(section, index) in reviewPayload.sections" :key="index" class="space-y-2 rounded-lg border p-3">
                    <div class="flex items-center justify-between gap-2">
                        <select v-model="section.type" class="border-input bg-background h-8 rounded-md border px-2 text-sm capitalize">
                            <option v-for="type in SECTION_TYPES" :key="type" :value="type">{{ type }}</option>
                        </select>
                        <Button size="icon" variant="destructive" @click="removeReviewSection(index)"><Trash2 class="size-4" /></Button>
                    </div>
                    <template v-if="awardLike(section.type)">
                        <Input v-model="section.organization" :placeholder="sectionOrgLabel(section.type)" />
                        <Input v-model="section.date" placeholder="Year / date (e.g. 2024)" />
                        <Input v-model="section.title" :placeholder="sectionTitleLabel(section.type)" />
                    </template>
                    <template v-else>
                        <Input v-if="showsDate(section.type)" v-model="section.date" placeholder="Dates" />
                        <Input v-model="section.title" :placeholder="sectionTitleLabel(section.type)" />
                        <Input v-if="showsOrganization(section.type)" v-model="section.organization" :placeholder="sectionOrgLabel(section.type)" />
                    </template>
                    <Textarea
                        :model-value="bulletsText(section)"
                        rows="3"
                        :placeholder="bulletsPlaceholder(section.type)"
                        @update:model-value="setBullets(section, String($event))"
                    />
                </div>
                <div class="flex flex-wrap gap-2">
                    <Button :disabled="approving" @click="approveImport">{{ approving ? 'Approving…' : 'Approve import' }}</Button>
                    <Button variant="ghost" @click="reviewImport = null; reviewPayload = null">Cancel</Button>
                </div>
            </CardContent>
        </Card>

        <div class="grid gap-4 xl:grid-cols-[280px_minmax(0,1fr)]">
            <Card>
                <CardHeader>
                    <CardTitle class="text-base">Profiles</CardTitle>
                </CardHeader>
                <CardContent class="space-y-2">
                    <button
                        v-for="profile in profiles"
                        :key="profile.id"
                        type="button"
                        class="hover:bg-muted w-full rounded-lg border px-3 py-2 text-left text-sm"
                        :class="selected?.id === profile.id ? 'border-primary bg-muted' : ''"
                        @click="openProfile(profile)"
                    >
                        <p class="font-medium">{{ profile.name }}</p>
                        <p class="text-muted-foreground text-xs">{{ profile.headline || 'No headline' }}</p>
                    </button>
                    <p v-if="profiles.length === 0" class="text-muted-foreground text-sm">No profiles yet.</p>
                </CardContent>
            </Card>

            <div v-if="selected" class="space-y-4">
                <Card>
                    <CardHeader class="flex-row flex-wrap items-start justify-between gap-2 space-y-0">
                        <div>
                            <CardTitle>{{ selected.name }}</CardTitle>
                            <CardDescription>Shown by résumé blocks and public variants. Theme → Resume controls public styling.</CardDescription>
                        </div>
                        <Button variant="destructive" size="sm" class="gap-1" @click="deleteProfile">
                            <Trash2 class="size-3.5" />Delete resume
                        </Button>
                    </CardHeader>
                    <CardContent class="grid gap-3 sm:grid-cols-2">
                        <div class="space-y-2"><Label>Name</Label><Input v-model="selected.name" /></div>
                        <div class="space-y-2"><Label>Headline</Label><Input v-model="selected.headline" /></div>
                        <div class="space-y-2"><Label>Email</Label><Input v-model="selected.email" type="email" autocomplete="email" /></div>
                        <div class="space-y-2"><Label>Phone</Label><Input v-model="selected.phone" autocomplete="tel" /></div>
                        <div class="space-y-2"><Label>Location</Label><Input v-model="selected.location" autocomplete="address-level2" /></div>
                        <div class="space-y-2">
                            <Label>Website</Label>
                            <Input v-model="selected.website" placeholder="ophiryahalom.com or https://…" autocomplete="url" />
                        </div>
                        <div class="space-y-2 sm:col-span-2"><Label>Summary</Label><Textarea v-model="selected.summary" rows="3" /></div>
                        <div class="space-y-3 sm:col-span-2 rounded-lg border p-3">
                            <div class="flex items-center justify-between gap-2">
                                <div>
                                    <p class="text-sm font-medium">Additional links</p>
                                    <p class="text-muted-foreground text-xs">GitHub, LinkedIn, portfolio, etc.</p>
                                </div>
                                <Button size="sm" variant="outline" class="gap-1" @click="addProfileLink">
                                    <Plus class="size-3.5" />Add link
                                </Button>
                            </div>
                            <div v-for="(link, index) in profileLinks" :key="index" class="grid gap-2 sm:grid-cols-[140px_minmax(0,1fr)_auto]">
                                <Input v-model="link.label" placeholder="GitHub" />
                                <Input v-model="link.url" placeholder="https://github.com/you" />
                                <Button size="icon" variant="ghost" class="text-destructive" @click="removeProfileLink(index)">
                                    <Trash2 class="size-4" />
                                </Button>
                            </div>
                            <p v-if="profileLinks.length === 0" class="text-muted-foreground text-xs">No extra links yet.</p>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex-row flex-wrap items-center justify-between gap-2 space-y-0">
                        <CardTitle>Sections</CardTitle>
                        <div class="flex flex-wrap gap-1.5">
                            <Button
                                v-for="type in SECTION_TYPES"
                                :key="type"
                                size="sm"
                                variant="outline"
                                class="gap-1 capitalize"
                                @click="addSection(type)"
                            >
                                <Plus class="size-3.5" />{{ type }}
                            </Button>
                        </div>
                    </CardHeader>
                    <CardContent class="space-y-3">
                        <div v-for="(section, index) in sections" :key="index" class="space-y-2 rounded-lg border p-3">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <select
                                    v-model="section.type"
                                    class="border-input bg-background h-8 rounded-md border px-2 text-sm capitalize"
                                >
                                    <option v-for="type in SECTION_TYPES" :key="type" :value="type">{{ type }}</option>
                                </select>
                                <div class="flex items-center gap-1">
                                    <Button size="icon" variant="outline" :disabled="index === 0" @click="moveSection(index, -1)">
                                        <ArrowUp class="size-4" />
                                    </Button>
                                    <Button size="icon" variant="outline" :disabled="index === sections.length - 1" @click="moveSection(index, 1)">
                                        <ArrowDown class="size-4" />
                                    </Button>
                                    <Button size="icon" variant="destructive" @click="removeSection(index)"><Trash2 class="size-4" /></Button>
                                </div>
                            </div>
                            <template v-if="awardLike(section.type)">
                                <Input v-model="section.organization" :placeholder="sectionOrgLabel(section.type)" />
                                <Input v-model="section.date" placeholder="Year / date (e.g. 2024)" />
                                <Input v-model="section.title" :placeholder="sectionTitleLabel(section.type)" />
                            </template>
                            <template v-else>
                                <Input v-model="section.title" :placeholder="sectionTitleLabel(section.type)" />
                                <Input
                                    v-if="showsOrganization(section.type)"
                                    v-model="section.organization"
                                    :placeholder="sectionOrgLabel(section.type)"
                                />
                                <Input v-if="showsDate(section.type)" v-model="section.date" placeholder="Dates (e.g. 2022 – Present)" />
                            </template>
                            <Textarea
                                :model-value="bulletsText(section)"
                                rows="3"
                                :placeholder="bulletsPlaceholder(section.type)"
                                @update:model-value="setBullets(section, String($event))"
                            />
                        </div>
                        <Button :disabled="saving" @click="save">Save résumé</Button>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Public variants</CardTitle>
                        <CardDescription>
                            Attach the polished PDF and/or DOCX visitors should download. The public page shows an animated PDF/DOCX chooser.
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-3">
                        <div class="flex flex-wrap gap-2">
                            <Input v-model="newVariantName" class="max-w-xs" placeholder="Variant name" />
                            <Button @click="publishVariant">Publish public variant</Button>
                        </div>
                        <div v-for="variant in variants" :key="variant.id" class="space-y-3 rounded-lg border px-3 py-3 text-sm">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <div class="min-w-0 flex-1 space-y-2">
                                    <div class="space-y-1">
                                        <Label class="text-xs">Variant title</Label>
                                        <Input v-model="variant.name" placeholder="Public résumé" />
                                    </div>
                                    <div class="space-y-1">
                                        <Label class="text-xs">Public slug</Label>
                                        <div class="flex items-center gap-1.5">
                                            <span class="text-muted-foreground shrink-0 text-xs">/resume/</span>
                                            <Input v-model="variant.slug" placeholder="ophir-yahalom" class="font-mono text-xs" />
                                        </div>
                                    </div>
                                </div>
                                <div class="flex flex-wrap items-center gap-1.5">
                                    <Badge :variant="variant.visibility === 'public' ? 'default' : 'secondary'">{{ variant.visibility }}</Badge>
                                    <Button size="sm" variant="outline" class="gap-1" as-child>
                                        <a :href="`/resume/${variant.slug}`" target="_blank" rel="noopener">
                                            <ExternalLink class="size-3.5" />View
                                        </a>
                                    </Button>
                                    <Button size="sm" variant="outline" class="gap-1" @click="shareVariant(variant)">
                                        <Share2 class="size-3.5" />Share
                                    </Button>
                                    <Button
                                        size="sm"
                                        variant="ghost"
                                        @click="setVisibility(variant, variant.visibility === 'public' ? 'private' : 'public')"
                                    >
                                        {{ variant.visibility === 'public' ? 'Unpublish' : 'Make public' }}
                                    </Button>
                                    <Button size="sm" variant="destructive" @click="deleteVariant(variant)">Delete</Button>
                                </div>
                            </div>
                            <div class="grid gap-2 sm:grid-cols-2">
                                <div class="space-y-1.5">
                                    <Label class="text-xs">Download PDF</Label>
                                    <div class="flex gap-1.5">
                                        <Input v-model="variant.download_pdf" placeholder="/storage/…pdf" class="text-xs" />
                                        <Button size="sm" variant="outline" @click="openDownloadPicker(variant, 'download_pdf')">Media</Button>
                                    </div>
                                </div>
                                <div class="space-y-1.5">
                                    <Label class="text-xs">Download DOCX</Label>
                                    <div class="flex gap-1.5">
                                        <Input v-model="variant.download_docx" placeholder="/storage/…docx" class="text-xs" />
                                        <Button size="sm" variant="outline" @click="openDownloadPicker(variant, 'download_docx')">Media</Button>
                                    </div>
                                </div>
                            </div>
                            <Button size="sm" variant="secondary" @click="saveVariantDetails(variant)">Save variant</Button>
                        </div>
                        <p v-if="shareUrl" class="text-muted-foreground break-all text-xs">Share: {{ shareUrl }}</p>
                    </CardContent>
                </Card>
            </div>
        </div>

        <div
            v-if="showMediaPicker"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
            @click.self="showMediaPicker = false"
        >
            <div class="bg-background max-h-[85vh] w-full max-w-4xl overflow-auto rounded-xl border p-4 shadow-xl">
                <div class="mb-3 flex items-center justify-between gap-2">
                    <strong>Choose download file</strong>
                    <Button size="sm" variant="ghost" @click="showMediaPicker = false">
                        <X class="size-4" />
                    </Button>
                </div>
                <MediaPanel
                    :items="mediaFiles"
                    :csrf="csrf"
                    :api="api"
                    selectable
                    @refreshed="mediaFiles = $event"
                    @select="pickMedia"
                />
            </div>
        </div>
    </section>
</template>
