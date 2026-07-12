<script setup lang="ts">
import { CheckCircle2, X, XCircle } from '@lucide/vue'
import { actionToasts, dismissActionToast } from '@/lib/actionToast'
</script>

<template>
    <Teleport to="body">
        <div class="dc-action-toast-layer" aria-live="polite">
            <div
                v-for="item in actionToasts()"
                :key="item.id"
                class="dc-action-toast"
                :class="item.variant === 'error' ? 'dc-action-toast--error' : 'dc-action-toast--success'"
                :style="{ top: `${item.top}px`, left: `${item.left}px` }"
                role="status"
            >
                <CheckCircle2 v-if="item.variant === 'success'" class="size-4 shrink-0" />
                <XCircle v-else class="size-4 shrink-0" />
                <span>{{ item.message }}</span>
                <button type="button" class="dc-action-toast-close" aria-label="Dismiss" @click="dismissActionToast(item.id)">
                    <X class="size-3.5" />
                </button>
            </div>
        </div>
    </Teleport>
</template>
