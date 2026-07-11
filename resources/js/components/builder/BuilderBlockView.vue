<script setup lang="ts">
import { computed } from 'vue'
import { VueDraggable } from 'vue-draggable-plus'
import { GripVertical, Plus, Trash2 } from '@lucide/vue'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { cn } from '@/lib/utils'
import BuilderBlockView from './BuilderBlockView.vue'

export type BuilderBlock = {
    id: string
    type: string
    props: Record<string, string | number | boolean>
    children?: BuilderBlock[]
}

const props = defineProps<{
    block: BuilderBlock
    selectedId: string | null
    depth?: number
}>()

const emit = defineEmits<{
    select: [block: BuilderBlock]
    update: []
    remove: [block: BuilderBlock]
    addChild: [parent: BuilderBlock, type: string]
}>()

const depth = computed(() => props.depth ?? 0)
const isSelected = computed(() => props.selectedId === props.block.id)
const isContainer = computed(() => props.block.type === 'section' || props.block.type === 'columns')

function selectSelf(event: Event): void {
    event.stopPropagation()
    emit('select', props.block)
}

function onInline(key: string, event: Event): void {
    const target = event.target as HTMLElement
    props.block.props[key] = target.innerText
    emit('update')
}

function headingTag(): string {
    const level = Math.min(6, Math.max(1, Number(props.block.props.level || 2)))
    return `h${level}`
}
</script>

<template>
    <div
        :class="cn(
            'group/block relative rounded-lg transition',
            isSelected ? 'ring-2 ring-primary ring-offset-2' : 'hover:ring-1 hover:ring-foreground/15',
            depth === 0 ? 'mb-3' : 'mb-2',
        )"
        @click="selectSelf"
    >
        <div
            class="absolute -top-3 left-3 z-10 flex items-center gap-1 opacity-0 transition group-hover/block:opacity-100"
            :class="{ 'opacity-100': isSelected }"
        >
            <Badge variant="secondary" class="gap-1 text-[10px] uppercase">
                <GripVertical class="size-3" />
                {{ block.type }}
            </Badge>
            <Button
                size="icon-sm"
                variant="destructive"
                class="opacity-90"
                @click.stop="emit('remove', block)"
            >
                <Trash2 class="size-3" />
            </Button>
        </div>

        <!-- Section -->
        <section
            v-if="block.type === 'section'"
            class="bg-background min-h-24 rounded-xl border border-dashed p-6 md:p-10"
            :style="{ padding: String(block.props.padding || '3rem 1.25rem') }"
        >
            <VueDraggable
                v-model="block.children!"
                class="min-h-16 space-y-3"
                group="builder-blocks"
                :animation="180"
                @end="emit('update')"
            >
                <BuilderBlockView
                    v-for="child in block.children"
                    :key="child.id"
                    :block="child"
                    :selected-id="selectedId"
                    :depth="depth + 1"
                    @select="emit('select', $event)"
                    @update="emit('update')"
                    @remove="emit('remove', $event)"
                    @add-child="(parent, type) => emit('addChild', parent, type)"
                />
            </VueDraggable>
            <div v-if="!block.children?.length" class="text-muted-foreground py-8 text-center text-sm">
                Drop blocks here or add from the left panel.
            </div>
            <div class="mt-3 flex flex-wrap gap-1.5 opacity-0 transition group-hover/block:opacity-100" :class="{ 'opacity-100': isSelected }">
                <Button size="sm" variant="outline" class="gap-1" @click.stop="emit('addChild', block, 'heading')">
                    <Plus class="size-3" /> Heading
                </Button>
                <Button size="sm" variant="outline" class="gap-1" @click.stop="emit('addChild', block, 'text')">
                    <Plus class="size-3" /> Text
                </Button>
                <Button size="sm" variant="outline" class="gap-1" @click.stop="emit('addChild', block, 'button')">
                    <Plus class="size-3" /> Button
                </Button>
                <Button size="sm" variant="outline" class="gap-1" @click.stop="emit('addChild', block, 'image')">
                    <Plus class="size-3" /> Image
                </Button>
            </div>
        </section>

        <!-- Columns -->
        <div
            v-else-if="block.type === 'columns'"
            class="rounded-xl border border-dashed p-4"
        >
            <VueDraggable
                v-model="block.children!"
                class="grid gap-4"
                :style="{ gridTemplateColumns: `repeat(${Math.max(1, Number(block.props.columns || 2))}, minmax(0, 1fr))` }"
                group="builder-blocks"
                :animation="180"
                @end="emit('update')"
            >
                <BuilderBlockView
                    v-for="child in block.children"
                    :key="child.id"
                    :block="child"
                    :selected-id="selectedId"
                    :depth="depth + 1"
                    @select="emit('select', $event)"
                    @update="emit('update')"
                    @remove="emit('remove', $event)"
                    @add-child="(parent, type) => emit('addChild', parent, type)"
                />
            </VueDraggable>
            <div v-if="!block.children?.length" class="text-muted-foreground py-6 text-center text-sm">
                Add blocks into these columns from the left panel.
            </div>
        </div>

        <!-- Heading -->
        <component
            :is="headingTag()"
            v-else-if="block.type === 'heading'"
            class="outline-none"
            contenteditable="true"
            @blur="onInline('text', $event)"
            @click.stop="selectSelf"
        >{{ block.props.text }}</component>

        <!-- Text -->
        <div
            v-else-if="block.type === 'text'"
            class="text-muted-foreground leading-relaxed outline-none"
            contenteditable="true"
            @blur="onInline('text', $event)"
            @click.stop="selectSelf"
        >{{ block.props.text }}</div>

        <!-- Button -->
        <div v-else-if="block.type === 'button'" class="py-1">
            <span
                class="bg-primary text-primary-foreground inline-flex rounded-lg px-4 py-2 text-sm font-semibold outline-none"
                contenteditable="true"
                @blur="onInline('text', $event)"
                @click.stop="selectSelf"
            >{{ block.props.text }}</span>
        </div>

        <!-- Image -->
        <div v-else-if="block.type === 'image'" class="overflow-hidden rounded-xl border">
            <img
                v-if="block.props.src"
                :src="String(block.props.src)"
                :alt="String(block.props.alt || '')"
                class="h-auto max-h-80 w-full object-cover"
            >
            <div v-else class="bg-muted text-muted-foreground flex h-40 items-center justify-center text-sm">
                Select this block and set an image URL in Settings
            </div>
        </div>

        <!-- Spacer -->
        <div
            v-else-if="block.type === 'spacer'"
            class="bg-muted/40 rounded border border-dashed"
            :style="{ height: String(block.props.height || '2rem') }"
        />

        <!-- Divider -->
        <hr v-else-if="block.type === 'divider'" class="border-border my-2">

        <!-- Feature-ish placeholders with visual chrome -->
        <div
            v-else-if="block.type.startsWith('resume') || block.type.startsWith('portfolio') || block.type === 'form'"
            class="bg-card rounded-xl border p-6"
        >
            <p class="mb-1 text-xs font-semibold tracking-wide uppercase opacity-60">{{ block.type.replace(/-/g, ' ') }}</p>
            <p class="text-sm">Live site will fill this from your résumé / projects / forms data.</p>
        </div>

        <div v-else class="bg-muted/30 rounded-lg border border-dashed p-4 text-sm">
            {{ block.type }}
        </div>
    </div>
</template>

<script lang="ts">
export default {
    name: 'BuilderBlockView',
}
</script>
