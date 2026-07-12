<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { ExternalLink, Plus, Share2, Trash2, Upload } from '@lucide/vue'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import { Badge } from '@/components/ui/badge'
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
}

type ResumeSection = {
    id?: number
    type: string
    title?: string | null
    organization?: string | null
    date?: string | null
    bullets?: string[] | string
}

type ResumeVariant = {
    id: number
    name: string
    slug: string
    visibility: string
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

const SECTION_TYPES = ['experience', 'education', 'skills', 'project', 'award', 'other']

const props = defineProps<{
    api: <T>(url: string, options?: RequestInit) => Promise<T>
}>()

const profiles = ref<ResumeProfile[]>([])
const selected = ref<ResumeProfile | null>(null)
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
    shareUrl.value = ''
    const rows = await props.api<Array<ResumeSection & { bullets: string }>>(`/resumes/${profile.id}/sections`)
    sections.value = rows.map((row) => ({
        ...row,
        bullets: typeof row.bullets === 'string' ? (JSON.parse(row.bullets || '[]') as string[]) : (row.bullets ?? []),
    }))
    variants.value = await props.api<ResumeVariant[]>(`/resumes/${profile.id}/variants`)
}

function addSection(type = 'experience'): void {
    sections.value.push({
        type,
        title: type === 'skills' ? 'Skills' : 'Role',
        organization: type === 'education' ? 'School' : 'Company',
        bullets: ['Detail'],
    })
}

function removeSection(index: number): void {
    sections.value.splice(index, 1)
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
        selected.value = await props.api<ResumeProfile>(`/resumes/${selected.value.id}`, {
            method: 'PUT',
            body: JSON.stringify(selected.value),
        })
        await props.api(`/resumes/${selected.value.id}/sections`, {
            method: 'PUT',
            body: JSON.stringify({
                sections: sections.value.map((section) => ({
                    type: section.type,
                    title: section.title,
                    organization: section.organization,
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

function exportVariant(variant: ResumeVariant): void {
    window.open(`/admin/api/resume-variants/${variant.id}/print`, '_blank')
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

        const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
        const response = await fetch('/admin/api/resumes/import', {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'X-CSRF-TOKEN': csrf,
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
    reviewPayload.value.sections.push({
        type: 'experience',
        title: 'Role',
        organization: 'Company',
        date: '',
        bullets: ['Detail'],
    })
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
                <p class="text-muted-foreground text-sm">Import PDF/DOCX/TXT, edit sections, and publish public variants.</p>
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
                    <Input v-model="section.date" placeholder="Dates" />
                    <Input v-model="section.title" placeholder="Title / role" />
                    <Input v-model="section.organization" placeholder="Organization" />
                    <Textarea
                        :model-value="bulletsText(section)"
                        rows="3"
                        placeholder="One bullet per line"
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
                    <CardHeader>
                        <CardTitle>{{ selected.name }}</CardTitle>
                        <CardDescription>Shown by résumé blocks and public variants.</CardDescription>
                    </CardHeader>
                    <CardContent class="grid gap-3 sm:grid-cols-2">
                        <div class="space-y-2"><Label>Name</Label><Input v-model="selected.name" /></div>
                        <div class="space-y-2"><Label>Headline</Label><Input v-model="selected.headline" /></div>
                        <div class="space-y-2"><Label>Email</Label><Input v-model="selected.email" type="email" /></div>
                        <div class="space-y-2"><Label>Phone</Label><Input v-model="selected.phone" /></div>
                        <div class="space-y-2"><Label>Location</Label><Input v-model="selected.location" /></div>
                        <div class="space-y-2"><Label>Website</Label><Input v-model="selected.website" /></div>
                        <div class="space-y-2 sm:col-span-2"><Label>Summary</Label><Textarea v-model="selected.summary" rows="3" /></div>
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
                            <div class="flex items-center justify-between gap-2">
                                <select
                                    v-model="section.type"
                                    class="border-input bg-background h-8 rounded-md border px-2 text-sm capitalize"
                                >
                                    <option v-for="type in SECTION_TYPES" :key="type" :value="type">{{ type }}</option>
                                </select>
                                <Button size="icon" variant="destructive" @click="removeSection(index)"><Trash2 class="size-4" /></Button>
                            </div>
                            <Input v-model="section.title" placeholder="Title / role" />
                            <Input v-model="section.organization" placeholder="Organization" />
                            <Textarea
                                :model-value="bulletsText(section)"
                                rows="3"
                                placeholder="One achievement per line"
                                @update:model-value="setBullets(section, String($event))"
                            />
                        </div>
                        <Button :disabled="saving" @click="save">Save résumé</Button>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Public variants</CardTitle>
                        <CardDescription>A public variant creates `/resume/{slug}` and print/export.</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-3">
                        <div class="flex flex-wrap gap-2">
                            <Input v-model="newVariantName" class="max-w-xs" placeholder="Variant name" />
                            <Button @click="publishVariant">Publish public variant</Button>
                        </div>
                        <div v-for="variant in variants" :key="variant.id" class="flex flex-wrap items-center justify-between gap-2 rounded-lg border px-3 py-2 text-sm">
                            <div>
                                <p class="font-medium">{{ variant.name }}</p>
                                <p class="text-muted-foreground text-xs">/resume/{{ variant.slug }}</p>
                            </div>
                            <div class="flex flex-wrap items-center gap-1.5">
                                <Badge :variant="variant.visibility === 'public' ? 'default' : 'secondary'">{{ variant.visibility }}</Badge>
                                <Button size="sm" variant="outline" class="gap-1" as-child>
                                    <a :href="`/resume/${variant.slug}`" target="_blank" rel="noopener">
                                        <ExternalLink class="size-3.5" />View
                                    </a>
                                </Button>
                                <Button size="sm" variant="outline" @click="exportVariant(variant)">Export</Button>
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
                            </div>
                        </div>
                        <p v-if="shareUrl" class="text-muted-foreground break-all text-xs">Share: {{ shareUrl }}</p>
                    </CardContent>
                </Card>
            </div>
        </div>
    </section>
</template>
