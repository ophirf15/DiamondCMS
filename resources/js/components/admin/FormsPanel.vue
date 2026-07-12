<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { ArrowLeft, Plus, Trash2 } from '@lucide/vue'
import { Badge } from '@/components/ui/badge'
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

type FormField = {
    name: string
    label: string
    type: string
    required: boolean
}

type FormRow = {
    id: number
    name: string
    slug: string
    status: string
    schema: { fields?: FormField[] }
    notifications?: { recipients?: string[] }
    success_message?: string
    submissions_count?: number
}

type Submission = {
    id: number
    status: string
    created_at: string
    payload: Record<string, unknown>
}

const props = defineProps<{
    api: <T>(url: string, options?: RequestInit) => Promise<T>
}>()

const forms = ref<FormRow[]>([])
const selected = ref<FormRow | null>(null)
const submissions = ref<Submission[]>([])
const view = ref<'list' | 'edit' | 'submissions'>('list')
const recipients = ref('')
const saving = ref(false)

const fieldTypes = ['text', 'email', 'textarea', 'url', 'number', 'checkbox', 'select', 'file']

const selectedFields = computed({
    get: () => selected.value?.schema?.fields ?? [],
    set: (fields: FormField[]) => {
        if (!selected.value) return
        selected.value.schema = { ...(selected.value.schema ?? {}), fields }
    },
})

async function load(): Promise<void> {
    try {
        forms.value = await props.api<FormRow[]>('/forms')
    } catch (error) {
        toast.error(error instanceof Error ? error.message : 'Could not load forms')
    }
}

async function ensureContact(): Promise<void> {
    try {
        await props.api('/forms/ensure-contact', { method: 'POST', body: '{}' })
        await load()
        toast.success('Contact form ready')
    } catch (error) {
        toast.error(error instanceof Error ? error.message : 'Could not create contact form')
    }
}

function openNew(): void {
    selected.value = {
        id: 0,
        name: 'Contact',
        slug: 'contact',
        status: 'published',
        schema: {
            fields: [
                { name: 'name', label: 'Name', type: 'text', required: true },
                { name: 'email', label: 'Email', type: 'email', required: true },
                { name: 'message', label: 'Message', type: 'textarea', required: true },
            ],
        },
        notifications: { recipients: [] },
        success_message: 'Thanks — your message is on its way.',
    }
    recipients.value = ''
    view.value = 'edit'
}

function openEdit(form: FormRow): void {
    selected.value = JSON.parse(JSON.stringify(form)) as FormRow
    recipients.value = (form.notifications?.recipients ?? []).join(', ')
    view.value = 'edit'
}

async function openSubmissions(form: FormRow): Promise<void> {
    selected.value = form
    submissions.value = await props.api<Submission[]>(`/forms/${form.id}/submissions`)
    view.value = 'submissions'
}

function addField(): void {
    if (!selected.value) return
    const fields = [...(selected.value.schema.fields ?? [])]
    fields.push({ name: `field_${fields.length + 1}`, label: 'New field', type: 'text', required: false })
    selected.value.schema = { fields }
}

function removeField(index: number): void {
    if (!selected.value) return
    const fields = [...(selected.value.schema.fields ?? [])]
    fields.splice(index, 1)
    selected.value.schema = { fields }
}

async function save(): Promise<void> {
    if (!selected.value) return
    saving.value = true
    try {
        const payload = {
            name: selected.value.name,
            slug: selected.value.slug,
            status: selected.value.status,
            schema: selected.value.schema,
            success_message: selected.value.success_message,
            notifications: {
                recipients: recipients.value.split(',').map((part) => part.trim()).filter(Boolean),
            },
        }
        if (selected.value.id) {
            selected.value = await props.api<FormRow>(`/forms/${selected.value.id}`, {
                method: 'PUT',
                body: JSON.stringify(payload),
            })
        } else {
            selected.value = await props.api<FormRow>('/forms', {
                method: 'POST',
                body: JSON.stringify(payload),
            })
        }
        await load()
        toast.success('Form saved')
        view.value = 'list'
    } catch (error) {
        toast.error(error instanceof Error ? error.message : 'Could not save form')
    } finally {
        saving.value = false
    }
}

onMounted(load)
</script>

<template>
    <section class="space-y-4">
        <div v-if="view === 'list'" class="space-y-4">
            <div class="flex flex-wrap items-end justify-between gap-3">
                <div>
                    <h1 class="text-3xl font-semibold tracking-tight">Forms</h1>
                    <p class="text-muted-foreground text-sm">Build contact forms, review submissions, and email notifications via SMTP.</p>
                </div>
                <div class="flex gap-2">
                    <Button variant="outline" @click="ensureContact">Ensure contact form</Button>
                    <Button class="gap-2" @click="openNew"><Plus class="size-4" /><span>New form</span></Button>
                </div>
            </div>

            <Card>
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Name</TableHead>
                            <TableHead>Slug</TableHead>
                            <TableHead>Status</TableHead>
                            <TableHead>Submissions</TableHead>
                            <TableHead class="w-[220px]" />
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-if="forms.length === 0">
                            <TableCell colspan="5" class="text-muted-foreground py-10 text-center">
                                No forms yet. Create a contact form to embed on pages.
                            </TableCell>
                        </TableRow>
                        <TableRow v-for="form in forms" :key="form.id">
                            <TableCell class="font-medium">{{ form.name }}</TableCell>
                            <TableCell class="text-muted-foreground">{{ form.slug }}</TableCell>
                            <TableCell><Badge :variant="form.status === 'published' ? 'default' : 'secondary'">{{ form.status }}</Badge></TableCell>
                            <TableCell>{{ form.submissions_count ?? 0 }}</TableCell>
                            <TableCell class="space-x-2">
                                <Button size="sm" variant="outline" @click="openEdit(form)">Edit</Button>
                                <Button size="sm" variant="ghost" @click="openSubmissions(form)">Submissions</Button>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </Card>
        </div>

        <div v-else-if="view === 'edit' && selected" class="space-y-4">
            <div class="flex items-center gap-2">
                <Button variant="ghost" size="sm" class="gap-1.5" @click="view = 'list'">
                    <ArrowLeft class="size-4" />
                    <span>Forms</span>
                </Button>
                <h1 class="text-2xl font-semibold tracking-tight">{{ selected.id ? 'Edit form' : 'New form' }}</h1>
            </div>

            <Card class="max-w-3xl">
                <CardHeader>
                    <CardTitle>Details</CardTitle>
                    <CardDescription>Embed with a Form block using slug <code>{{ selected.slug || '…' }}</code>.</CardDescription>
                </CardHeader>
                <CardContent class="space-y-4">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="space-y-2">
                            <Label>Name</Label>
                            <Input v-model="selected.name" />
                        </div>
                        <div class="space-y-2">
                            <Label>Slug</Label>
                            <Input v-model="selected.slug" />
                        </div>
                        <div class="space-y-2">
                            <Label>Status</Label>
                            <select v-model="selected.status" class="border-input bg-background flex h-9 w-full rounded-md border px-3 text-sm">
                                <option value="draft">Draft</option>
                                <option value="published">Published</option>
                                <option value="archived">Archived</option>
                            </select>
                        </div>
                        <div class="space-y-2 sm:col-span-2">
                            <Label>Notification emails (comma-separated)</Label>
                            <Input v-model="recipients" placeholder="you@example.com" />
                        </div>
                        <div class="space-y-2 sm:col-span-2">
                            <Label>Success message</Label>
                            <Textarea v-model="selected.success_message" rows="2" />
                        </div>
                    </div>
                </CardContent>
            </Card>

            <Card class="max-w-3xl">
                <CardHeader class="flex-row items-center justify-between space-y-0">
                    <CardTitle>Fields</CardTitle>
                    <Button size="sm" variant="outline" class="gap-1.5" @click="addField">
                        <Plus class="size-3.5" />
                        <span>Add field</span>
                    </Button>
                </CardHeader>
                <CardContent class="space-y-3">
                    <div
                        v-for="(field, index) in selectedFields"
                        :key="index"
                        class="grid gap-2 rounded-lg border p-3 md:grid-cols-[1fr_1fr_140px_100px_auto]"
                    >
                        <Input v-model="field.label" placeholder="Label" />
                        <Input v-model="field.name" placeholder="name" />
                        <select v-model="field.type" class="border-input bg-background flex h-9 w-full rounded-md border px-3 text-sm">
                            <option v-for="type in fieldTypes" :key="type" :value="type">{{ type }}</option>
                        </select>
                        <label class="flex items-center gap-2 text-sm">
                            <input v-model="field.required" type="checkbox" class="size-4">
                            Required
                        </label>
                        <Button size="icon" variant="destructive" @click="removeField(index)">
                            <Trash2 class="size-4" />
                        </Button>
                    </div>
                    <Button :disabled="saving" @click="save">Save form</Button>
                </CardContent>
            </Card>
        </div>

        <div v-else-if="view === 'submissions' && selected" class="space-y-4">
            <div class="flex items-center gap-2">
                <Button variant="ghost" size="sm" class="gap-1.5" @click="view = 'list'">
                    <ArrowLeft class="size-4" />
                    <span>Forms</span>
                </Button>
                <h1 class="text-2xl font-semibold tracking-tight">{{ selected.name }} submissions</h1>
                <a class="text-primary ml-auto text-sm underline" :href="`/admin/forms/${selected.id}/submissions.csv`">Download CSV</a>
            </div>
            <Card>
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>When</TableHead>
                            <TableHead>Status</TableHead>
                            <TableHead>Payload</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-if="submissions.length === 0">
                            <TableCell colspan="3" class="text-muted-foreground py-10 text-center">No submissions yet.</TableCell>
                        </TableRow>
                        <TableRow v-for="row in submissions" :key="row.id">
                            <TableCell class="whitespace-nowrap">{{ row.created_at }}</TableCell>
                            <TableCell><Badge variant="secondary">{{ row.status }}</Badge></TableCell>
                            <TableCell class="max-w-xl truncate text-sm">{{ JSON.stringify(row.payload) }}</TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </Card>
        </div>
    </section>
</template>
