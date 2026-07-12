import { reactive } from 'vue'

export type ActionToastItem = {
    id: number
    message: string
    variant: 'success' | 'error'
    top: number
    left: number
}

const state = reactive({
    items: [] as ActionToastItem[],
    seq: 0,
})

export function actionToasts(): ActionToastItem[] {
    return state.items
}

export function showActionToast(
    anchor: Event | HTMLElement | null | undefined,
    message: string,
    variant: 'success' | 'error' = 'success',
): void {
    let el: HTMLElement | null = null
    if (anchor instanceof HTMLElement) {
        el = anchor
    } else if (anchor && 'currentTarget' in anchor && anchor.currentTarget instanceof HTMLElement) {
        el = anchor.currentTarget
    } else if (anchor && 'target' in anchor && anchor.target instanceof HTMLElement) {
        el = anchor.target.closest('button, a, [role="button"]')
    }

    if (!el) {
        // Fallback: bottom-center of viewport
        pushToast(message, variant, window.innerHeight - 88, Math.max(16, window.innerWidth / 2 - 140))
        return
    }

    const rect = el.getBoundingClientRect()
    const width = 280
    let left = rect.left + rect.width / 2 - width / 2
    left = Math.min(Math.max(12, left), window.innerWidth - width - 12)
    let top = rect.bottom + 10
    if (top + 64 > window.innerHeight) {
        top = Math.max(12, rect.top - 64)
    }

    pushToast(message, variant, top, left)
}

function pushToast(message: string, variant: 'success' | 'error', top: number, left: number): void {
    const id = ++state.seq
    state.items.push({ id, message, variant, top, left })
    window.setTimeout(() => {
        const index = state.items.findIndex((item) => item.id === id)
        if (index >= 0) state.items.splice(index, 1)
    }, 2600)
}

export function dismissActionToast(id: number): void {
    const index = state.items.findIndex((item) => item.id === id)
    if (index >= 0) state.items.splice(index, 1)
}
