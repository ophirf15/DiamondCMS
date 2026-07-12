<script setup lang="ts">
import { onMounted, ref, watch } from 'vue'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table'
import { toast } from 'vue-sonner'

type PageOption = {
    id: number
    title: string
    slug: string
    meta_title?: string | null
    meta_description?: string | null
    status: string
}

type Redirect = {
    id: number
    source: string
    target: string
    status_code: number
    is_active: boolean | number
    hit_count?: number
}

type Activity = {
    id: number
    event: string
    created_at: string
}

type AuditResult = {
    id: number
    score: number
    findings: Array<{ type: string, message: string }>
}

const props = defineProps<{
    api: <T>(url: string, options?: RequestInit) => Promise<T>
    pages: PageOption[]
}>()

const emit = defineEmits<{ refreshed: [] }>()

const selectedPageId = ref<number | null>(null)
const metaTitle = ref('')
const metaDescription = ref('')
const redirects = ref<Redirect[]>([])
const activity = ref<Activity[]>([])
const newRedirect = ref({ source: '', target: '', status_code: 301 })
const revisions = ref<Array<{ id: number, revision: number, created_at: string }>>([])
const audit = ref<AuditResult | null>(null)

function selectPage(id: number): void {
    selectedPageId.value = id
    const page = props.pages.find((candidate) => candidate.id === id)
    metaTitle.value = page?.meta_title ?? ''
    metaDescription.value = page?.meta_description ?? ''
    audit.value = null
    loadRevisions()
}

async function load(): Promise<void> {
    redirects.value = await props.api<Redirect[]>('/redirects')
    activity.value = await props.api<Activity[]>('/activity')
    if (!selectedPageId.value && props.pages[0]) {
        selectPage(props.pages[0].id)
    }
}

async function loadRevisions(): Promise<void> {
    if (!selectedPageId.value) return
    revisions.value = await props.api(`/pages/${selectedPageId.value}/revisions`)
}

async function saveSeo(): Promise<void> {
    if (!selectedPageId.value) return
    try {
        await props.api(`/pages/${selectedPageId.value}`, {
            method: 'PUT',
            body: JSON.stringify({
                meta_title: metaTitle.value,
                meta_description: metaDescription.value,
            }),
        })
        emit('refreshed')
        toast.success('SEO fields saved')
    } catch (error) {
        toast.error(error instanceof Error ? error.message : 'Could not save SEO')
    }
}

async function runAudit(): Promise<void> {
    if (!selectedPageId.value) return
    try {
        audit.value = await props.api<AuditResult>(`/seo/audit-page/${selectedPageId.value}`, {
            method: 'POST',
            body: '{}',
        })
        toast.success(`Audit score: ${audit.value.score}`)
    } catch (error) {
        toast.error(error instanceof Error ? error.message : 'Audit failed')
    }
}

async function addRedirect(): Promise<void> {
    try {
        await props.api('/redirects', {
            method: 'POST',
            body: JSON.stringify(newRedirect.value),
        })
        newRedirect.value = { source: '', target: '', status_code: 301 }
        redirects.value = await props.api<Redirect[]>('/redirects')
        toast.success('Redirect created')
    } catch (error) {
        toast.error(error instanceof Error ? error.message : 'Could not create redirect')
    }
}

async function deleteRedirect(id: number): Promise<void> {
    await props.api(`/redirects/${id}`, { method: 'DELETE' })
    redirects.value = await props.api<Redirect[]>('/redirects')
    toast.success('Redirect deleted')
}

async function duplicatePage(): Promise<void> {
    if (!selectedPageId.value) return
    await props.api(`/pages/${selectedPageId.value}/duplicate`, { method: 'POST', body: '{}' })
    emit('refreshed')
    toast.success('Page duplicated as draft')
}

async function rollback(revision: number): Promise<void> {
    if (!selectedPageId.value) return
    await props.api(`/pages/${selectedPageId.value}/rollback/${revision}`, { method: 'POST', body: '{}' })
    emit('refreshed')
    await loadRevisions()
    toast.success(`Rolled back to revision ${revision}`)
}

watch(() => props.pages, () => {
    if (selectedPageId.value) {
        const page = props.pages.find((candidate) => candidate.id === selectedPageId.value)
        if (page) {
            metaTitle.value = page.meta_title ?? metaTitle.value
            metaDescription.value = page.meta_description ?? metaDescription.value
        }
    }
})

onMounted(load)
</script>

<template>
    <section class="space-y-4">
        <div>
            <h1 class="text-3xl font-semibold tracking-tight">SEO & pages</h1>
            <p class="text-muted-foreground text-sm">Meta fields, audits, redirects, revisions, and activity.</p>
        </div>

        <Card class="max-w-2xl">
            <CardHeader>
                <CardTitle>Page SEO</CardTitle>
                <CardDescription>Title and description for search / social cards.</CardDescription>
            </CardHeader>
            <CardContent class="space-y-3">
                <div class="space-y-2">
                    <Label>Page</Label>
                    <select
                        class="border-input bg-background flex h-9 w-full rounded-md border px-3 text-sm"
                        :value="selectedPageId ?? ''"
                        @change="selectPage(Number(($event.target as HTMLSelectElement).value))"
                    >
                        <option v-for="page in pages" :key="page.id" :value="page.id">{{ page.title }} (/{{ page.slug }})</option>
                    </select>
                </div>
                <div class="space-y-2"><Label>Meta title</Label><Input v-model="metaTitle" /></div>
                <div class="space-y-2"><Label>Meta description</Label><Textarea v-model="metaDescription" rows="3" /></div>
                <div class="flex flex-wrap gap-2">
                    <Button @click="saveSeo">Save SEO</Button>
                    <Button variant="outline" @click="runAudit">Run audit</Button>
                    <Button variant="outline" @click="duplicatePage">Duplicate page</Button>
                </div>
                <div v-if="audit" class="rounded-lg border p-3 text-sm">
                    <p class="font-medium">Score: {{ audit.score }}</p>
                    <ul v-if="audit.findings.length" class="text-muted-foreground mt-2 list-disc space-y-1 pl-5">
                        <li v-for="(finding, index) in audit.findings" :key="index">{{ finding.message }}</li>
                    </ul>
                    <p v-else class="text-muted-foreground mt-1">No findings.</p>
                </div>
            </CardContent>
        </Card>

        <Card class="max-w-2xl">
            <CardHeader>
                <CardTitle>Revisions</CardTitle>
            </CardHeader>
            <CardContent>
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>#</TableHead>
                            <TableHead>When</TableHead>
                            <TableHead />
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-if="revisions.length === 0">
                            <TableCell colspan="3" class="text-muted-foreground text-center">No revisions yet.</TableCell>
                        </TableRow>
                        <TableRow v-for="row in revisions" :key="row.id">
                            <TableCell>{{ row.revision }}</TableCell>
                            <TableCell>{{ row.created_at }}</TableCell>
                            <TableCell>
                                <Button size="sm" variant="outline" @click="rollback(row.revision)">Restore</Button>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </CardContent>
        </Card>

        <Card class="max-w-2xl">
            <CardHeader>
                <CardTitle>Redirects</CardTitle>
            </CardHeader>
            <CardContent class="space-y-3">
                <div class="grid gap-2 md:grid-cols-[1fr_1fr_100px_auto]">
                    <Input v-model="newRedirect.source" placeholder="/old-path" />
                    <Input v-model="newRedirect.target" placeholder="/new-path" />
                    <Input v-model.number="newRedirect.status_code" type="number" />
                    <Button @click="addRedirect">Add</Button>
                </div>
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>From</TableHead>
                            <TableHead>To</TableHead>
                            <TableHead>Code</TableHead>
                            <TableHead>Hits</TableHead>
                            <TableHead />
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="row in redirects" :key="row.id">
                            <TableCell>{{ row.source }}</TableCell>
                            <TableCell>{{ row.target }}</TableCell>
                            <TableCell>{{ row.status_code }}</TableCell>
                            <TableCell>{{ row.hit_count ?? 0 }}</TableCell>
                            <TableCell>
                                <Button size="sm" variant="destructive" @click="deleteRedirect(row.id)">Delete</Button>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </CardContent>
        </Card>

        <Card class="max-w-2xl">
            <CardHeader>
                <CardTitle>Recent activity</CardTitle>
            </CardHeader>
            <CardContent class="space-y-2">
                <div v-for="row in activity" :key="row.id" class="flex justify-between gap-3 border-b py-2 text-sm last:border-0">
                    <span>{{ row.event }}</span>
                    <span class="text-muted-foreground whitespace-nowrap">{{ row.created_at }}</span>
                </div>
                <p v-if="activity.length === 0" class="text-muted-foreground text-sm">No activity yet.</p>
            </CardContent>
        </Card>
    </section>
</template>
