import { createApp } from 'vue'
import LiveEditorApp from './components/LiveEditorApp.vue'
import { initPublicSite } from './public'

createApp(LiveEditorApp).mount('#live-editor-app')
// Theme toggles are rendered by Vue after mount.
queueMicrotask(() => initPublicSite())
