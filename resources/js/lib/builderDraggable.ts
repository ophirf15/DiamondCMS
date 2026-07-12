export const builderDraggableOptions = {
    animation: 180,
    handle: '.dc-drag-handle',
    delay: 300,
    delayOnTouchOnly: true,
    touchStartThreshold: 8,
    filter: 'input, textarea, [contenteditable="true"], .dc-button, a, button, select',
    preventOnFilter: false,
} as const
