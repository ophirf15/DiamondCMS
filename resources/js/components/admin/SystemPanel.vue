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
    created_at: string
}

const props = defineProps<{
    api: <T>(url: string, options?: RequestInit) => Promise<T>
}>()

const backups = ref<Backup[]>([])
const updates = ref<UpdateLog[]>([])
const lastExport = ref('')
const stageForm = ref({ source_path: '', checksum: '', version: '' })
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
        toast.success(`Backup #${result.id} created`)
    } catch (error) {
        toast.error(error instanceof Error ? error.message : 'Backup failed')
    } finally {
        busy.value = false
    }
}

async function exportSite(): Promise<void> {
    busy.value = true
    try {
        const result = await props.api<{ path: string, filename: string, note: string }>('/exports', {
            method: 'POST',
            body: '{}',
        })
        lastExport.value = `${result.filename} → ${result.path}`
        await load()
        toast.success(result.note)
    } catch (error) {
        toast.error(error instanceof Error ? error.message : 'Export failed')
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

async function stageUpdate(): Promise<void> {
    busy.value = true
    try {
        const result = await props.api<{ id: number }>('/updates/stage', {
            method: 'POST',
            body: JSON.stringify(stageForm.value),
        })
        stageForm.value = { source_path: '', checksum: '', version: '' }
        await load()
        toast.success(`Update staged as #${result.id}`)
    } catch (error) {
        toast.error(error instanceof Error ? error.message : 'Staging failed — checksum must match')
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
            <p class="text-muted-foreground text-sm">Backups, exports (no secrets), and checksum-verified update staging.</p>
        </div>

        <Card class="max-w-2xl">
            <CardHeader>
                <CardTitle>Backups & export</CardTitle>
                <CardDescription>JSON backups stay on the server disk. Exports exclude API keys and .env.</CardDescription>
            </CardHeader>
            <CardContent class="flex flex-wrap gap-2">
                <Button :disabled="busy" @click="createBackup">Create backup</Button>
                <Button :disabled="busy" variant="outline" @click="exportSite">Export site ZIP</Button>
                <p v-if="lastExport" class="text-muted-foreground w-full break-all text-xs">{{ lastExport }}</p>
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
                <CardTitle>Stage update</CardTitle>
                <CardDescription>Provide a local ZIP path and expected SHA-256. Staging verifies checksum before recording.</CardDescription>
            </CardHeader>
            <CardContent class="space-y-3">
                <div class="space-y-2"><Label>Source path</Label><Input v-model="stageForm.source_path" placeholder="C:\releases\diamondcms-x.y.z.zip" /></div>
                <div class="space-y-2"><Label>Expected SHA-256</Label><Input v-model="stageForm.checksum" class="font-mono text-sm" /></div>
                <div class="space-y-2"><Label>Version</Label><Input v-model="stageForm.version" placeholder="0.2.0" /></div>
                <Button :disabled="busy" @click="stageUpdate">Stage update</Button>
            </CardContent>
        </Card>

        <Card class="max-w-2xl">
            <CardHeader>
                <CardTitle>Update log</CardTitle>
            </CardHeader>
            <CardContent class="space-y-2">
                <div v-for="row in updates" :key="row.id" class="flex justify-between gap-3 border-b py-2 text-sm last:border-0">
                    <span>v{{ row.version }} · {{ row.status }}</span>
                    <span class="text-muted-foreground">{{ row.created_at }}</span>
                </div>
                <p v-if="updates.length === 0" class="text-muted-foreground text-sm">No staged updates yet.</p>
            </CardContent>
        </Card>
    </section>
</template>
