import { createApp } from 'vue'
import InstallerApp from './components/InstallerApp.vue'

const el = document.getElementById('installer-app')
if (el) {
    const boot = JSON.parse(el.dataset.boot ?? '{}')
    createApp(InstallerApp, { boot }).mount(el)
}
