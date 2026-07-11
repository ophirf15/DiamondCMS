import { createApp } from 'vue'
import AuthLoginApp from './components/AuthLoginApp.vue'

const el = document.getElementById('auth-login-app')
if (el) {
    const boot = JSON.parse(el.dataset.boot ?? '{}')
    createApp(AuthLoginApp, { boot }).mount(el)
}
