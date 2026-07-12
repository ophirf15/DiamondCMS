<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { Plus, Trash2 } from '@lucide/vue'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import IconPicker from '@/components/ui/IconPicker.vue'
import SocialBrandIcon from '@/components/builder/SocialBrandIcon.vue'
import { toast } from 'vue-sonner'
import type { SocialLinkRecord } from '@/lib/socialLinks'

const props = defineProps<{
    api: <T>(url: string, options?: RequestInit) => Promise<T>
}>()

const emit = defineEmits<{
    saved: [links: SocialLinkRecord[]]
}>()

const links = ref<SocialLinkRecord[]>([])
const saving = ref(false)
const iconPickerOpen = ref(false)
const iconPickerIndex = ref(0)

function blankLink(): SocialLinkRecord {
    return {
        id: crypto.randomUUID(),
        label: 'New link',
        url: 'https://',
        icon: 'link',
    }
}

async function load(): Promise<void> {
    try {
        links.value = await props.api<SocialLinkRecord[]>('/social-links')
    } catch (error) {
        toast.error(error instanceof Error ? error.message : 'Could not load social links')
    }
}

function addLink(): void {
    links.value = [...links.value, blankLink()]
}

function removeLink(index: number): void {
    links.value = links.value.filter((_, i) => i !== index)
}

function updateLink(index: number, key: keyof SocialLinkRecord, value: string): void {
    links.value = links.value.map((row, i) => (i === index ? { ...row, [key]: value } : row))
}

function openIconPicker(index: number): void {
    iconPickerIndex.value = index
    iconPickerOpen.value = true
}

function onIconPicked(slug: string): void {
    updateLink(iconPickerIndex.value, 'icon', slug)
}

async function save(): Promise<void> {
    saving.value = true
    try {
        links.value = await props.api<SocialLinkRecord[]>('/social-links', {
            method: 'PUT',
            body: JSON.stringify({ links: links.value }),
        })
        emit('saved', links.value)
        toast.success('Social links saved')
    } catch (error) {
        toast.error(error instanceof Error ? error.message : 'Could not save social links')
    } finally {
        saving.value = false
    }
}

onMounted(load)
</script>

<template>
    <section class="space-y-4">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight sm:text-3xl">Social links</h1>
                <p class="text-muted-foreground text-sm">
                    Maintain your link library once, then choose which links appear in the footer or on each page block.
                </p>
            </div>
            <Button class="gap-2" :disabled="saving" @click="save">
                <span>{{ saving ? 'Saving…' : 'Save library' }}</span>
            </Button>
        </div>

        <Card>
            <CardHeader>
                <CardTitle>Link library</CardTitle>
                <CardDescription>
                    Add LinkedIn, Instagram, Facebook, a Printables shop, email, or any custom URL. Footer and Social links blocks pick from this list.
                </CardDescription>
            </CardHeader>
            <CardContent class="space-y-4">
                <div class="flex justify-end">
                    <Button size="sm" variant="outline" class="gap-1" @click="addLink">
                        <Plus class="size-3.5" />
                        Add link
                    </Button>
                </div>

                <p v-if="!links.length" class="text-muted-foreground rounded-lg border border-dashed p-6 text-sm">
                    No links yet. Add your first one — then choose which to show in Theme → Footer or on a Social links block per page.
                </p>

                <div v-for="(item, index) in links" :key="item.id" class="space-y-3 rounded-xl border p-4">
                    <div class="flex items-start justify-between gap-2">
                        <button
                            type="button"
                            class="hover:border-primary flex items-center gap-2 rounded-lg border px-2 py-1.5 text-xs transition"
                            @click="openIconPicker(index)"
                        >
                            <SocialBrandIcon :slug="item.icon" :label="item.label" :url="item.url" :size="16" />
                            {{ item.icon || 'Pick icon' }}
                        </button>
                        <Button size="sm" variant="ghost" class="text-destructive h-7 px-2" @click="removeLink(index)">
                            <Trash2 class="size-3.5" />
                        </Button>
                    </div>
                    <div class="space-y-1">
                        <Label :for="`social-label-${item.id}`">Label</Label>
                        <Input
                            :id="`social-label-${item.id}`"
                            :model-value="item.label"
                            placeholder="LinkedIn"
                            @update:model-value="updateLink(index, 'label', String($event))"
                        />
                    </div>
                    <div class="space-y-1">
                        <Label :for="`social-url-${item.id}`">URL</Label>
                        <Input
                            :id="`social-url-${item.id}`"
                            :model-value="item.url"
                            placeholder="https://linkedin.com/in/you"
                            @update:model-value="updateLink(index, 'url', String($event))"
                        />
                    </div>
                </div>
            </CardContent>
        </Card>

        <IconPicker
            v-model:open="iconPickerOpen"
            :model-value="links[iconPickerIndex]?.icon || null"
            title="Social icon"
            @update:model-value="onIconPicked"
        />
    </section>
</template>
