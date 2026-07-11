<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import { VueDraggable } from 'vue-draggable-plus';

type BlockType = 'section' | 'columns' | 'heading' | 'text' | 'image' | 'button' | 'spacer' | 'divider' | 'html' | 'resume-summary' | 'resume-experience' | 'resume-download';

type BuilderBlock = {
    id: string;
    type: BlockType;
    props: Record<string, string | number | boolean>;
    children?: BuilderBlock[];
};

type BuilderDocument = {
    schema: number;
    title: string;
    blocks: BuilderBlock[];
};

type Page = {
    id: number;
    title: string;
    slug: string;
    status: string;
    builder_json: string | BuilderDocument | null;
};

type RegistryBlock = {
    type: BlockType;
    label: string;
    defaults: Record<string, string | number | boolean>;
};

type Dashboard = {
    pages: number;
    published: number;
    drafts: number;
    media: number;
};

const csrf = document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? '';
const activePanel = ref('dashboard');
const dashboard = ref<Dashboard>({ pages: 0, published: 0, drafts: 0, media: 0 });
const pages = ref<Page[]>([]);
const registry = ref<RegistryBlock[]>([]);
const selectedPage = ref<Page | null>(null);
const documentState = ref<BuilderDocument>(emptyDocument('Untitled page'));
const selectedBlock = ref<BuilderBlock | null>(null);
const history = ref<BuilderDocument[]>([]);
const future = ref<BuilderDocument[]>([]);
const status = ref('');
const mediaFiles = ref<unknown[]>([]);
const templates = ref<unknown[]>([]);
const resumeName = ref('');

const serializedDocument = computed(() => JSON.stringify(documentState.value, null, 2));

function emptyDocument(title: string): BuilderDocument {
    return {
        schema: 1,
        title,
        blocks: [
            {
                id: crypto.randomUUID(),
                type: 'section',
                props: { padding: '4rem 1rem' },
                children: [
                    { id: crypto.randomUUID(), type: 'heading', props: { level: 1, text: title } },
                    { id: crypto.randomUUID(), type: 'text', props: { text: 'Write something useful here.' } },
                ],
            },
        ],
    };
}

async function api<T>(url: string, options: RequestInit = {}): Promise<T> {
    const response = await fetch(`/admin/api${url}`, {
        headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrf, ...(options.headers ?? {}) },
        ...options,
    });

    if (!response.ok) {
        throw new Error(await response.text());
    }

    return (await response.json()) as T;
}

function snapshot(): void {
    history.value.push(JSON.parse(JSON.stringify(documentState.value)) as BuilderDocument);
    future.value = [];
    localStorage.setItem('diamondcms.builder.recovery', JSON.stringify(documentState.value));
}

function undo(): void {
    const previous = history.value.pop();
    if (!previous) return;
    future.value.push(JSON.parse(JSON.stringify(documentState.value)) as BuilderDocument);
    documentState.value = previous;
}

function redo(): void {
    const next = future.value.pop();
    if (!next) return;
    history.value.push(JSON.parse(JSON.stringify(documentState.value)) as BuilderDocument);
    documentState.value = next;
}

function addBlock(block: RegistryBlock): void {
    snapshot();
    documentState.value.blocks.push({ id: crypto.randomUUID(), type: block.type, props: { ...block.defaults }, children: block.type === 'section' || block.type === 'columns' ? [] : undefined });
}

function duplicateBlock(block: BuilderBlock): void {
    snapshot();
    documentState.value.blocks.push({ ...JSON.parse(JSON.stringify(block)), id: crypto.randomUUID() });
}

function deleteBlock(block: BuilderBlock): void {
    snapshot();
    documentState.value.blocks = documentState.value.blocks.filter((candidate) => candidate.id !== block.id);
    selectedBlock.value = null;
}

function blockLabel(block: BuilderBlock): string {
    return registry.value.find((candidate) => candidate.type === block.type)?.label ?? block.type;
}

function parsePageDocument(page: Page): BuilderDocument {
    if (typeof page.builder_json === 'string') {
        return JSON.parse(page.builder_json) as BuilderDocument;
    }

    return page.builder_json ?? emptyDocument(page.title);
}

async function load(): Promise<void> {
    dashboard.value = await api<Dashboard>('/dashboard');
    const pageResult = await api<{ data: Page[] }>('/pages');
    pages.value = pageResult.data;
    registry.value = (await api<{ blocks: RegistryBlock[] }>('/builder/registry')).blocks;
    mediaFiles.value = (await api<{ data: unknown[] }>('/media')).data;
    templates.value = await api<unknown[]>('/templates');
    const recovered = localStorage.getItem('diamondcms.builder.recovery');
    if (recovered) {
        documentState.value = JSON.parse(recovered) as BuilderDocument;
        status.value = 'Recovered unsaved builder work from this browser.';
    }
}

async function createPage(): Promise<void> {
    const title = prompt('Page title');
    if (!title) return;
    const page = await api<Page>('/pages', {
        method: 'POST',
        body: JSON.stringify({ title, slug: title.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, ''), status: 'draft', builder_json: emptyDocument(title) }),
    });
    pages.value.unshift(page);
    selectPage(page);
}

function selectPage(page: Page): void {
    selectedPage.value = page;
    documentState.value = parsePageDocument(page);
    selectedBlock.value = null;
}

async function savePage(statusOverride?: string): Promise<void> {
    snapshot();
    if (!selectedPage.value) {
        await createPage();
        return;
    }

    const page = await api<Page>(`/pages/${selectedPage.value.id}`, {
        method: 'PUT',
        body: JSON.stringify({
            title: selectedPage.value.title,
            slug: selectedPage.value.slug,
            status: statusOverride ?? selectedPage.value.status,
            builder_json: documentState.value,
        }),
    });
    selectedPage.value = page;
    pages.value = pages.value.map((candidate) => (candidate.id === page.id ? page : candidate));
    localStorage.removeItem('diamondcms.builder.recovery');
    status.value = 'Page saved.';
}

async function uploadMedia(event: Event): Promise<void> {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0];
    if (!file) return;
    const form = new FormData();
    form.append('file', file);
    const response = await fetch('/admin/api/media', { method: 'POST', headers: { 'X-CSRF-TOKEN': csrf, Accept: 'application/json' }, body: form });
    if (!response.ok) throw new Error(await response.text());
    await load();
}

async function seedTemplates(): Promise<void> {
    await api('/templates/seed', { method: 'POST', body: '{}' });
    templates.value = await api<unknown[]>('/templates');
}

async function createResume(): Promise<void> {
    if (!resumeName.value) return;
    await api('/resumes', { method: 'POST', body: JSON.stringify({ name: resumeName.value }) });
    resumeName.value = '';
    status.value = 'Resume profile created.';
}

onMounted(load);
</script>

<template>
    <div class="dc-admin">
        <aside class="dc-admin-sidebar">
            <h1>DiamondCMS</h1>
            <button @click="activePanel = 'dashboard'">Dashboard</button>
            <button @click="activePanel = 'pages'">Pages</button>
            <button @click="activePanel = 'builder'">Builder</button>
            <button @click="activePanel = 'media'">Media</button>
            <button @click="activePanel = 'design'">Design</button>
            <button @click="activePanel = 'resumes'">Resumes</button>
            <form method="post" action="/logout">
                <input type="hidden" name="_token" :value="csrf">
                <button>Logout</button>
            </form>
        </aside>

        <main class="dc-admin-main">
            <p v-if="status" class="dc-status">{{ status }}</p>

            <section v-if="activePanel === 'dashboard'" class="dc-admin-grid">
                <article><strong>{{ dashboard.pages }}</strong><span>Pages</span></article>
                <article><strong>{{ dashboard.published }}</strong><span>Published</span></article>
                <article><strong>{{ dashboard.drafts }}</strong><span>Drafts</span></article>
                <article><strong>{{ dashboard.media }}</strong><span>Media files</span></article>
            </section>

            <section v-if="activePanel === 'pages'">
                <div class="dc-admin-toolbar">
                    <h2>Pages</h2>
                    <button class="dc-button" @click="createPage">New page</button>
                </div>
                <table class="dc-table">
                    <thead><tr><th>Title</th><th>Slug</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                        <tr v-for="page in pages" :key="page.id">
                            <td>{{ page.title }}</td>
                            <td>{{ page.slug }}</td>
                            <td>{{ page.status }}</td>
                            <td><button @click="selectPage(page); activePanel = 'builder'">Edit</button></td>
                        </tr>
                    </tbody>
                </table>
            </section>

            <section v-if="activePanel === 'builder'" class="dc-builder">
                <div class="dc-builder-library">
                    <h2>Blocks</h2>
                    <button v-for="block in registry" :key="block.type" @click="addBlock(block)">{{ block.label }}</button>
                    <h2>Layers</h2>
                    <button v-for="block in documentState.blocks" :key="block.id" @click="selectedBlock = block">{{ blockLabel(block) }}</button>
                </div>
                <div class="dc-builder-canvas">
                    <div class="dc-admin-toolbar">
                        <strong>{{ selectedPage?.title ?? documentState.title }}</strong>
                        <button @click="undo">Undo</button>
                        <button @click="redo">Redo</button>
                        <button @click="savePage()">Autosave now</button>
                        <button @click="savePage('published')">Publish</button>
                    </div>
                    <VueDraggable v-model="documentState.blocks" class="dc-canvas-list" @end="snapshot">
                        <article v-for="block in documentState.blocks" :key="block.id" class="dc-canvas-block" :class="{ selected: selectedBlock?.id === block.id }" tabindex="0" @click="selectedBlock = block">
                            <strong>{{ blockLabel(block) }}</strong>
                            <p>{{ block.props.text || block.props.html || block.props.url || 'Responsive builder block' }}</p>
                            <button @click.stop="duplicateBlock(block)">Duplicate</button>
                            <button @click.stop="deleteBlock(block)">Delete</button>
                        </article>
                    </VueDraggable>
                </div>
                <div class="dc-builder-inspector">
                    <h2>Inspector</h2>
                    <template v-if="selectedBlock">
                        <label v-for="(_, key) in selectedBlock.props" :key="key">{{ key }}
                            <input v-model="selectedBlock.props[key]" @change="snapshot">
                        </label>
                    </template>
                    <details>
                        <summary>JSON</summary>
                        <pre>{{ serializedDocument }}</pre>
                    </details>
                </div>
            </section>

            <section v-if="activePanel === 'media'">
                <h2>Media library</h2>
                <input type="file" @change="uploadMedia">
                <p>{{ mediaFiles.length }} media item(s)</p>
            </section>

            <section v-if="activePanel === 'design'">
                <h2>Design and templates</h2>
                <button class="dc-button" @click="seedTemplates">Install starter templates</button>
                <p>{{ templates.length }} template(s) available.</p>
            </section>

            <section v-if="activePanel === 'resumes'">
                <h2>Resumes</h2>
                <label>Profile name <input v-model="resumeName"></label>
                <button class="dc-button" @click="createResume">Create resume profile</button>
            </section>
        </main>
    </div>
</template>
