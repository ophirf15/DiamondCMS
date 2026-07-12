<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Badge } from '@/components/ui/badge'
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table'
import { toast } from 'vue-sonner'

type Backup = {
    id: number
    type: string
    path: string
    size: number
    status: string
    created_at: string
}

type UpdateLog = {
    id: number
    version: string
    status: string
    checksum?: string | null
    source_url?: string | null
    created_at: string
}

type UpdateCheck = {
    repo: string
    current: string
    latest: string | null
    newer: boolean
    notes: string
    html_url?: string | null
    zip_url?: string | null
    zip_name?: string | null
}

type ExportResult = {
    path: string
    filename: string
    relative: string
    size: number
    media_files: number
    media_library_files?: number
    other_files?: number
    missing_media?: string[]
    checksum: string
    download_url?: string
    note?: string
}

type ImportResult = {
    ok: boolean
    job_id?: number
    tables?: string[]
    media_files?: number
    errors?: string[]
    warnings?: string[]
}

const props = defineProps<{
    api: <T>(url: string, options?: RequestInit) => Promise<T>
}>()

const csrf = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement | null)?.content ?? ''

const backups = ref<Backup[]>([])
const updates = ref<UpdateLog[]>([])
const updateCheck = ref<UpdateCheck | null>(null)
const lastExport = ref('')
const importMode = ref<'replace' | 'merge'>('replace')
const importFile = ref<File | null>(null)
const uploadVersion = ref('')
const uploadPackage = ref<File | null>(null)
const busy = ref(false)

async function load(): Promise<void> {
    backups.value = await props.api<Backup[]>('/backups')
    updates.value = await props.api<UpdateLog[]>('/updates')
}

async function createBackup(): Promise<void> {
    busy.value = true
    try {
        const result = await props.api<{ id: number }>('/backups', {
            method: 'POST',
            body: JSON.stringify({ type: 'full' }),
        })
        await load()
        toast.success(`Server backup #${result.id} created`)
    } catch (error) {
        toast.error(error instanceof Error ? error.message : 'Backup failed')
    } finally {
        busy.value = false
    }
}

async function exportSite(): Promise<void> {
    busy.value = true
    try {
        const result = await props.api<ExportResult>('/exports', {
            method: 'POST',
            body: '{}',
        })
        lastExport.value = [
            result.filename,
            formatSize(result.size),
            `${result.media_files} files packed`,
            `${result.media_library_files ?? 0} from media library`,
        ].join(' · ')
        await load()
        if (result.download_url) {
            const link = document.createElement('a')
            link.href = result.download_url
            link.download = result.filename
            document.body.appendChild(link)
            link.click()
            link.remove()
        }
        if ((result.missing_media?.length ?? 0) > 0) {
            toast.warning(
                `Package downloaded, but ${result.missing_media!.length} media path(s) were missing on disk`,
            )
        } else {
            toast.success(result.note ?? 'Complete site package downloaded (content + all media)')
        }
    } catch (error) {
        toast.error(error instanceof Error ? error.message : 'Export failed')
    } finally {
        busy.value = false
    }
}

async function importPackage(): Promise<void> {
    if (!importFile.value) {
        toast.error('Choose a DiamondCMS site ZIP first')
        return
    }
    const modeLabel = importMode.value === 'replace' ? 'replace all content and media' : 'merge into existing content'
    if (!confirm(`Import this package and ${modeLabel}? A pre-import backup is created first.`)) {
        return
    }

    busy.value = true
    try {
        const body = new FormData()
        body.append('package', importFile.value)
        body.append('mode', importMode.value)

        const response = await fetch('/admin/api/imports/upload', {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'X-CSRF-TOKEN': csrf,
            },
            body,
        })
        if (!response.ok) {
            throw new Error(await response.text())
        }
        const result = (await response.json()) as ImportResult
        if (!result.ok) {
            throw new Error(result.errors?.join(', ') || 'Import failed')
        }
        importFile.value = null
        await load()
        toast.success(
            `Import complete · ${(result.tables ?? []).length} tables · ${result.media_files ?? 0} files restored`,
        )
    } catch (error) {
        toast.error(error instanceof Error ? error.message : 'Import failed')
    } finally {
        busy.value = false
    }
}

async function restore(backup: Backup): Promise<void> {
    if (!confirm(`Restore backup #${backup.id}? This replaces content tables.`)) return
    busy.value = true
    try {
        await props.api(`/backups/${backup.id}/restore`, { method: 'POST', body: '{}' })
        toast.success('Restore completed')
    } catch (error) {
        toast.error(error instanceof Error ? error.message : 'Restore failed')
    } finally {
        busy.value = false
    }
}

async function checkUpdates(): Promise<void> {
    busy.value = true
    try {
        updateCheck.value = await props.api<UpdateCheck>('/updates/check')
        if (updateCheck.value.newer) {
            toast.success(`Update available: v${updateCheck.value.latest}`)
        } else {
            toast.success(`You’re on the latest release (v${updateCheck.value.current})`)
        }
    } catch (error) {
        toast.error(error instanceof Error ? error.message : 'Could not check GitHub releases')
    } finally {
        busy.value = false
    }
}

async function downloadUpdate(): Promise<void> {
    busy.value = true
    try {
        const staged = await props.api<{ id: number, version: string }>('/updates/download', {
            method: 'POST',
            body: '{}',
        })
        await load()
        toast.success(`Staged v${staged.version} — review then Apply`)
    } catch (error) {
        toast.error(error instanceof Error ? error.message : 'Download failed')
    } finally {
        busy.value = false
    }
}

async function applyUpdate(row: UpdateLog): Promise<void> {
    if (!confirm(`Apply software update v${row.version}? Site content, media, and .env are preserved. A pre-update DB backup is created first.`)) {
        return
    }
    busy.value = true
    try {
        const result = await props.api<{ ok: boolean, version: string, backup_id: number }>(`/updates/${row.id}/apply`, {
            method: 'POST',
            body: '{}',
        })
        await load()
        updateCheck.value = await props.api<UpdateCheck>('/updates/check').catch(() => updateCheck.value)
        toast.success(`Updated to v${result.version} (backup #${result.backup_id})`)
    } catch (error) {
        toast.error(error instanceof Error ? error.message : 'Apply failed — check update log')
        await load()
    } finally {
        busy.value = false
    }
}

async function uploadReleasePackage(): Promise<void> {
    if (!uploadPackage.value || !uploadVersion.value.trim()) {
        toast.error('Choose a ZIP and enter the version')
        return
    }
    busy.value = true
    try {
        const body = new FormData()
        body.append('package', uploadPackage.value)
        body.append('version', uploadVersion.value.trim())
        const response = await fetch('/admin/api/updates/upload', {
            method: 'POST',
            headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf },
            body,
        })
        if (!response.ok) throw new Error(await response.text())
        uploadPackage.value = null
        uploadVersion.value = ''
        await load()
        toast.success('Release staged from upload')
    } catch (error) {
        toast.error(error instanceof Error ? error.message : 'Upload failed')
    } finally {
        busy.value = false
    }
}

function formatSize(bytes: number): string {
    if (!bytes) return '—'
    if (bytes < 1024) return `${bytes} B`
    if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`
    return `${(bytes / (1024 * 1024)).toFixed(1)} MB`
}

onMounted(load)
</script>

<template>
    <section class="space-y-4">
        <div>
            <h1 class="text-3xl font-semibold tracking-tight">System</h1>
            <p class="text-muted-foreground text-sm">
                Move a complete local site to production: pages, theme, media library, and settings in one ZIP.
            </p>
        </div>

        <Card class="max-w-2xl">
            <CardHeader>
                <CardTitle>Complete site copy</CardTitle>
                <CardDescription>
                    Download packs the entire site: every page, setting, theme token, portfolio/resume record,
                    <strong>and every media library file</strong> (originals + resized variants), plus form uploads.
                    Excluded: .env, admin users/passwords, SMTP, and AI keys — set those on the live host after import.
                </CardDescription>
            </CardHeader>
            <CardContent class="space-y-4">
                <div class="flex flex-wrap gap-2">
                    <Button :disabled="busy" @click="exportSite">Download complete site ZIP (with media)</Button>
                    <Button :disabled="busy" variant="outline" @click="createBackup">Server-only JSON (no media files)</Button>
                </div>
                <p v-if="lastExport" class="text-muted-foreground break-all text-xs">{{ lastExport }}</p>

                <div class="space-y-3 border-t pt-4">
                    <Label for="site-package">Import site package</Label>
                    <Input
                        id="site-package"
                        type="file"
                        accept=".zip,application/zip"
                        :disabled="busy"
                        @change="importFile = ($event.target as HTMLInputElement).files?.[0] ?? null"
                    />
                    <div class="flex flex-wrap items-center gap-3">
                        <Label class="text-muted-foreground text-xs">Mode</Label>
                        <select
                            v-model="importMode"
                            class="border-input bg-background h-9 rounded-md border px-3 text-sm"
                            :disabled="busy"
                        >
                            <option value="replace">Replace (clone local → live)</option>
                            <option value="merge">Merge (keep existing rows when IDs differ)</option>
                        </select>
                        <Button :disabled="busy || !importFile" @click="importPackage">Upload &amp; apply</Button>
                    </div>
                    <p class="text-muted-foreground text-xs">
                        Use <strong>Replace</strong> when uploading your local build to the public site so content and media match exactly.
                        A pre-import backup is saved first.
                    </p>
                </div>
            </CardContent>
        </Card>

        <Card>
            <CardHeader>
                <CardTitle>Recent backups</CardTitle>
            </CardHeader>
            <CardContent>
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>ID</TableHead>
                            <TableHead>Type</TableHead>
                            <TableHead>Size</TableHead>
                            <TableHead>Status</TableHead>
                            <TableHead>When</TableHead>
                            <TableHead />
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-if="backups.length === 0">
                            <TableCell colspan="6" class="text-muted-foreground text-center">No backups yet.</TableCell>
                        </TableRow>
                        <TableRow v-for="row in backups" :key="row.id">
                            <TableCell>{{ row.id }}</TableCell>
                            <TableCell>{{ row.type }}</TableCell>
                            <TableCell>{{ formatSize(row.size) }}</TableCell>
                            <TableCell><Badge>{{ row.status }}</Badge></TableCell>
                            <TableCell>{{ row.created_at }}</TableCell>
                            <TableCell>
                                <Button size="sm" variant="outline" :disabled="busy" @click="restore(row)">Restore</Button>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </CardContent>
        </Card>

        <Card class="max-w-2xl">
            <CardHeader>
                <CardTitle>Software updates</CardTitle>
                <CardDescription>
                    Pull DiamondCMS releases from GitHub. Updates replace application code only —
                    they do not overwrite pages, media, forms, or <code class="text-xs">.env</code>.
                </CardDescription>
            </CardHeader>
            <CardContent class="space-y-4">
                <div class="flex flex-wrap gap-2">
                    <Button :disabled="busy" @click="checkUpdates">Check GitHub</Button>
                    <Button
                        :disabled="busy || !updateCheck?.newer || !updateCheck.zip_url"
                        variant="outline"
                        @click="downloadUpdate"
                    >
                        Download &amp; stage latest
                    </Button>
                </div>
                <div v-if="updateCheck" class="text-muted-foreground space-y-1 text-sm">
                    <p>Running <strong>v{{ updateCheck.current }}</strong>
                        <span v-if="updateCheck.latest"> · Latest <strong>v{{ updateCheck.latest }}</strong></span>
                        <span v-if="updateCheck.newer"> · update available</span>
                        <span v-else> · up to date</span>
                    </p>
                    <p class="text-xs">Repo: {{ updateCheck.repo }}</p>
                    <a
                        v-if="updateCheck.html_url"
                        :href="updateCheck.html_url"
                        target="_blank"
                        rel="noopener"
                        class="text-xs underline"
                    >Release notes</a>
                </div>

                <div class="space-y-2 border-t pt-4">
                    <Label>Manual ZIP upload (recovery)</Label>
                    <Input v-model="uploadVersion" placeholder="Version e.g. 0.2.0" :disabled="busy" />
                    <Input
                        type="file"
                        accept=".zip,application/zip"
                        :disabled="busy"
                        @change="uploadPackage = ($event.target as HTMLInputElement).files?.[0] ?? null"
                    />
                    <Button :disabled="busy || !uploadPackage || !uploadVersion" variant="outline" @click="uploadReleasePackage">
                        Stage uploaded ZIP
                    </Button>
                </div>
            </CardContent>
        </Card>

        <Card class="max-w-2xl">
            <CardHeader>
                <CardTitle>Update log</CardTitle>
                <CardDescription>Staged packages can be applied when ready. Failed applies leave a DB backup you can restore.</CardDescription>
            </CardHeader>
            <CardContent class="space-y-2">
                <div
                    v-for="row in updates"
                    :key="row.id"
                    class="flex flex-wrap items-center justify-between gap-3 border-b py-2 text-sm last:border-0"
                >
                    <div>
                        <span>v{{ row.version }} · </span>
                        <Badge>{{ row.status }}</Badge>
                        <span class="text-muted-foreground ml-2 text-xs">{{ row.created_at }}</span>
                    </div>
                    <Button
                        v-if="row.status === 'staged'"
                        size="sm"
                        :disabled="busy"
                        @click="applyUpdate(row)"
                    >
                        Apply
                    </Button>
                </div>
                <p v-if="updates.length === 0" class="text-muted-foreground text-sm">No updates staged yet.</p>
            </CardContent>
        </Card>
    </section>
</template>
