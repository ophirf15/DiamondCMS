<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
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

type Provider = {
    id: number
    provider: string
    name: string
    default_model?: string | null
    is_enabled: boolean | number
}

type Generation = {
    id: number
    task: string
    status: string
    page_id?: number | null
    created_at: string
}

const props = defineProps<{
    api: <T>(url: string, options?: RequestInit) => Promise<T>
}>()

const emit = defineEmits<{ refreshed: [] }>()

const providers = ref<Provider[]>([])
const generations = ref<Generation[]>([])
const providerForm = ref({
    provider: 'openai',
    name: '',
    api_key: '',
    default_model: '',
})
const draft = ref({ title: '', summary: '' })

async function load(): Promise<void> {
    providers.value = await props.api<Provider[]>('/ai/providers')
    generations.value = await props.api<Generation[]>('/ai/generations')
}

async function saveProvider(): Promise<void> {
    try {
        await props.api('/ai/providers', {
            method: 'POST',
            body: JSON.stringify({
                provider: providerForm.value.provider,
                name: providerForm.value.name || undefined,
                api_key: providerForm.value.api_key || undefined,
                default_model: providerForm.value.default_model || undefined,
                is_enabled: true,
            }),
        })
        providerForm.value.api_key = ''
        await load()
        toast.success('Provider saved (key encrypted at rest)')
    } catch (error) {
        toast.error(error instanceof Error ? error.message : 'Could not save provider')
    }
}

async function generateDraft(): Promise<void> {
    if (!draft.value.title.trim()) return
    try {
        const result = await props.api<{ generation_id: number }>('/ai/generate-draft-page', {
            method: 'POST',
            body: JSON.stringify(draft.value),
        })
        draft.value = { title: '', summary: '' }
        await load()
        toast.success(`Draft generation #${result.generation_id} awaiting approval`)
    } catch (error) {
        toast.error(error instanceof Error ? error.message : 'Could not generate draft')
    }
}

async function approve(generation: Generation): Promise<void> {
    try {
        const result = await props.api<{ page_id: number }>(`/ai/generations/${generation.id}/approve`, {
            method: 'POST',
            body: '{}',
        })
        await load()
        emit('refreshed')
        toast.success(`Approved as draft page #${result.page_id}`)
    } catch (error) {
        toast.error(error instanceof Error ? error.message : 'Approval failed')
    }
}

onMounted(load)
</script>

<template>
    <section class="space-y-4">
        <div>
            <h1 class="text-3xl font-semibold tracking-tight">AI</h1>
            <p class="text-muted-foreground text-sm">
                Configure a provider, generate draft pages, and approve before anything is published.
            </p>
        </div>

        <Card class="max-w-2xl">
            <CardHeader>
                <CardTitle>Provider</CardTitle>
                <CardDescription>API keys are encrypted. Leave blank to keep an existing key.</CardDescription>
            </CardHeader>
            <CardContent class="space-y-3">
                <div class="grid gap-3 sm:grid-cols-2">
                    <div class="space-y-2">
                        <Label>Provider</Label>
                        <select
                            v-model="providerForm.provider"
                            class="border-input bg-background flex h-9 w-full rounded-md border px-3 text-sm"
                        >
                            <option value="openai">OpenAI</option>
                            <option value="anthropic">Anthropic</option>
                            <option value="gemini">Gemini</option>
                        </select>
                    </div>
                    <div class="space-y-2">
                        <Label>Display name</Label>
                        <Input v-model="providerForm.name" placeholder="Optional" />
                    </div>
                    <div class="space-y-2 sm:col-span-2">
                        <Label>API key</Label>
                        <Input v-model="providerForm.api_key" type="password" autocomplete="off" placeholder="sk-…" />
                    </div>
                    <div class="space-y-2 sm:col-span-2">
                        <Label>Default model</Label>
                        <Input v-model="providerForm.default_model" placeholder="Optional override" />
                    </div>
                </div>
                <Button @click="saveProvider">Save provider</Button>
                <div class="flex flex-wrap gap-2 pt-2">
                    <Badge v-for="row in providers" :key="row.id" variant="secondary">
                        {{ row.provider }} · {{ row.name }}{{ row.is_enabled ? '' : ' (off)' }}
                    </Badge>
                </div>
            </CardContent>
        </Card>

        <Card class="max-w-2xl">
            <CardHeader>
                <CardTitle>Generate draft page</CardTitle>
                <CardDescription>Creates a pending generation — nothing is live until you approve.</CardDescription>
            </CardHeader>
            <CardContent class="space-y-3">
                <div class="space-y-2"><Label>Title</Label><Input v-model="draft.title" /></div>
                <div class="space-y-2"><Label>Brief / summary</Label><Textarea v-model="draft.summary" rows="3" /></div>
                <Button @click="generateDraft">Generate draft</Button>
            </CardContent>
        </Card>

        <Card>
            <CardHeader>
                <CardTitle>Pending & recent generations</CardTitle>
            </CardHeader>
            <CardContent>
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>ID</TableHead>
                            <TableHead>Task</TableHead>
                            <TableHead>Status</TableHead>
                            <TableHead>Created</TableHead>
                            <TableHead />
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-if="generations.length === 0">
                            <TableCell colspan="5" class="text-muted-foreground text-center">No generations yet.</TableCell>
                        </TableRow>
                        <TableRow v-for="row in generations" :key="row.id">
                            <TableCell>{{ row.id }}</TableCell>
                            <TableCell>{{ row.task }}</TableCell>
                            <TableCell><Badge>{{ row.status }}</Badge></TableCell>
                            <TableCell>{{ row.created_at }}</TableCell>
                            <TableCell>
                                <Button
                                    v-if="row.status === 'pending_approval'"
                                    size="sm"
                                    @click="approve(row)"
                                >
                                    Approve → draft page
                                </Button>
                                <span v-else-if="row.page_id" class="text-muted-foreground text-xs">Page #{{ row.page_id }}</span>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </CardContent>
        </Card>
    </section>
</template>
