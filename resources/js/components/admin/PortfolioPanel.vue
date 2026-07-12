<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { ExternalLink, Plus, Trash2 } from '@lucide/vue'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
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

type Category = { id: number, name: string, slug: string }

type Project = {
    id: number
    title: string
    slug: string
    status: string
    visibility: string
    is_featured: boolean | number
    summary?: string | null
    case_study?: string | null
    url?: string | null
    category_id?: number | null
    skills?: string | string[]
}

const props = defineProps<{
    api: <T>(url: string, options?: RequestInit) => Promise<T>
}>()

const projects = ref<Project[]>([])
const categories = ref<Category[]>([])
const editing = ref<Project | null>(null)
const newCategory = ref('')
const form = ref({
    title: '',
    summary: '',
    case_study: '',
    status: 'published',
    visibility: 'public',
    is_featured: true,
    url: '',
    skills: '',
    category_id: null as number | null,
})

function skillsText(project: Project): string {
    if (Array.isArray(project.skills)) return project.skills.join(', ')
    if (typeof project.skills === 'string') {
        try {
            const parsed = JSON.parse(project.skills) as string[]
            return Array.isArray(parsed) ? parsed.join(', ') : project.skills
        } catch {
            return project.skills
        }
    }
    return ''
}

async function load(): Promise<void> {
    projects.value = await props.api<Project[]>('/portfolio/projects')
    categories.value = await props.api<Category[]>('/portfolio/categories')
}

async function createCategory(): Promise<void> {
    if (!newCategory.value.trim()) return
    await props.api('/portfolio/categories', {
        method: 'POST',
        body: JSON.stringify({ name: newCategory.value.trim() }),
    })
    newCategory.value = ''
    await load()
    toast.success('Category created')
}

async function create(): Promise<void> {
    if (!form.value.title.trim()) return
    try {
        await props.api('/portfolio/projects', {
            method: 'POST',
            body: JSON.stringify({
                title: form.value.title,
                summary: form.value.summary,
                case_study: form.value.case_study || null,
                status: form.value.status,
                visibility: form.value.visibility,
                is_featured: form.value.is_featured,
                url: form.value.url || null,
                category_id: form.value.category_id,
                skills: form.value.skills.split(',').map((s) => s.trim()).filter(Boolean),
            }),
        })
        form.value = { title: '', summary: '', case_study: '', status: 'published', visibility: 'public', is_featured: true, url: '', skills: '', category_id: null }
        await load()
        toast.success('Project created')
    } catch (error) {
        toast.error(error instanceof Error ? error.message : 'Could not create project')
    }
}

function startEdit(project: Project): void {
    editing.value = {
        ...project,
        skills: skillsText(project),
    }
}

async function saveEdit(): Promise<void> {
    if (!editing.value) return
    const skills = typeof editing.value.skills === 'string'
        ? editing.value.skills.split(',').map((s) => s.trim()).filter(Boolean)
        : (editing.value.skills ?? [])
    try {
        await props.api(`/portfolio/projects/${editing.value.id}`, {
            method: 'PUT',
            body: JSON.stringify({
                title: editing.value.title,
                summary: editing.value.summary,
                case_study: editing.value.case_study,
                url: editing.value.url || null,
                category_id: editing.value.category_id,
                status: editing.value.status,
                visibility: editing.value.visibility,
                is_featured: !!editing.value.is_featured,
                skills,
            }),
        })
        editing.value = null
        await load()
        toast.success('Project updated')
    } catch (error) {
        toast.error(error instanceof Error ? error.message : 'Could not update project')
    }
}

async function toggleFeatured(project: Project): Promise<void> {
    await props.api(`/portfolio/projects/${project.id}`, {
        method: 'PUT',
        body: JSON.stringify({ is_featured: !project.is_featured }),
    })
    await load()
}

async function setStatus(project: Project, status: string): Promise<void> {
    await props.api(`/portfolio/projects/${project.id}`, {
        method: 'PUT',
        body: JSON.stringify({ status, visibility: status === 'published' ? 'public' : project.visibility }),
    })
    await load()
}

async function remove(project: Project): Promise<void> {
    if (!confirm(`Delete “${project.title}”?`)) return
    await props.api(`/portfolio/projects/${project.id}`, { method: 'DELETE' })
    if (editing.value?.id === project.id) editing.value = null
    await load()
    toast.success('Project deleted')
}

onMounted(load)
</script>

<template>
    <section class="space-y-4">
        <div>
            <h1 class="text-3xl font-semibold tracking-tight">Portfolio</h1>
            <p class="text-muted-foreground text-sm">Projects on /projects and featured grid blocks.</p>
        </div>

        <Card class="max-w-2xl">
            <CardHeader>
                <CardTitle>Categories</CardTitle>
            </CardHeader>
            <CardContent class="space-y-3">
                <div class="flex flex-wrap gap-2">
                    <Badge v-for="cat in categories" :key="cat.id" variant="secondary">{{ cat.name }}</Badge>
                    <span v-if="categories.length === 0" class="text-muted-foreground text-sm">No categories yet.</span>
                </div>
                <div class="flex gap-2">
                    <Input v-model="newCategory" placeholder="New category name" />
                    <Button variant="outline" @click="createCategory">Add</Button>
                </div>
            </CardContent>
        </Card>

        <Card class="max-w-2xl">
            <CardHeader>
                <CardTitle>New project</CardTitle>
            </CardHeader>
            <CardContent class="space-y-3">
                <div class="space-y-2"><Label>Title</Label><Input v-model="form.title" /></div>
                <div class="space-y-2"><Label>Summary</Label><Textarea v-model="form.summary" rows="2" /></div>
                <div class="space-y-2"><Label>Case study</Label><Textarea v-model="form.case_study" rows="3" /></div>
                <div class="grid gap-3 sm:grid-cols-2">
                    <div class="space-y-2"><Label>URL</Label><Input v-model="form.url" placeholder="https://" /></div>
                    <div class="space-y-2"><Label>Skills (comma-separated)</Label><Input v-model="form.skills" placeholder="Laravel, Vue" /></div>
                    <div class="space-y-2 sm:col-span-2">
                        <Label>Category</Label>
                        <select
                            v-model="form.category_id"
                            class="border-input bg-background flex h-9 w-full rounded-md border px-3 text-sm"
                        >
                            <option :value="null">None</option>
                            <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                        </select>
                    </div>
                </div>
                <label class="flex items-center gap-2 text-sm">
                    <input v-model="form.is_featured" type="checkbox" class="size-4">
                    Featured on homepage grids
                </label>
                <Button class="gap-2" @click="create"><Plus class="size-4" />Create project</Button>
            </CardContent>
        </Card>

        <Card v-if="editing" class="max-w-2xl border-primary">
            <CardHeader>
                <CardTitle>Edit {{ editing.title }}</CardTitle>
            </CardHeader>
            <CardContent class="space-y-3">
                <div class="space-y-2"><Label>Title</Label><Input v-model="editing.title" /></div>
                <div class="space-y-2"><Label>Summary</Label><Textarea v-model="editing.summary" rows="2" /></div>
                <div class="space-y-2"><Label>Case study</Label><Textarea v-model="editing.case_study" rows="4" /></div>
                <div class="grid gap-3 sm:grid-cols-2">
                    <div class="space-y-2"><Label>URL</Label><Input v-model="editing.url" /></div>
                    <div class="space-y-2">
                        <Label>Skills</Label>
                        <Input
                            :model-value="typeof editing.skills === 'string' ? editing.skills : skillsText(editing)"
                            @update:model-value="editing.skills = String($event)"
                        />
                    </div>
                </div>
                <div class="space-y-2">
                    <Label>Category</Label>
                    <select
                        v-model="editing.category_id"
                        class="border-input bg-background flex h-9 w-full rounded-md border px-3 text-sm"
                    >
                        <option :value="null">None</option>
                        <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                    </select>
                </div>
                <div class="flex gap-2">
                    <Button @click="saveEdit">Save changes</Button>
                    <Button variant="ghost" @click="editing = null">Cancel</Button>
                </div>
            </CardContent>
        </Card>

        <Card>
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>Title</TableHead>
                        <TableHead>Status</TableHead>
                        <TableHead>Featured</TableHead>
                        <TableHead />
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow v-if="projects.length === 0">
                        <TableCell colspan="4" class="text-muted-foreground py-8 text-center">No projects yet.</TableCell>
                    </TableRow>
                    <TableRow v-for="project in projects" :key="project.id">
                        <TableCell>
                            <p class="font-medium">{{ project.title }}</p>
                            <p class="text-muted-foreground text-xs">/projects/{{ project.slug }}</p>
                        </TableCell>
                        <TableCell><Badge>{{ project.status }} / {{ project.visibility }}</Badge></TableCell>
                        <TableCell>{{ project.is_featured ? 'Yes' : 'No' }}</TableCell>
                        <TableCell class="space-x-1">
                            <Button size="sm" variant="outline" class="gap-1" as-child>
                                <a :href="`/projects/${project.slug}`" target="_blank" rel="noopener">
                                    <ExternalLink class="size-3.5" />
                                </a>
                            </Button>
                            <Button size="sm" variant="outline" @click="startEdit(project)">Edit</Button>
                            <Button size="sm" variant="outline" @click="toggleFeatured(project)">Featured</Button>
                            <Button
                                size="sm"
                                variant="ghost"
                                @click="setStatus(project, project.status === 'published' ? 'draft' : 'published')"
                            >
                                {{ project.status === 'published' ? 'Unpublish' : 'Publish' }}
                            </Button>
                            <Button size="sm" variant="destructive" @click="remove(project)"><Trash2 class="size-3.5" /></Button>
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </Card>
    </section>
</template>
