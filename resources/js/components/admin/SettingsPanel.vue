<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { Send } from '@lucide/vue'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { toast } from 'vue-sonner'

type PageOption = { id: number, title: string, slug: string, status: string }
type SettingRow = { key: string, value: string, group: string }
type MailConfig = {
    host?: string
    port?: number
    username?: string
    encryption?: string
    from_address?: string
    from_name?: string
}

const props = defineProps<{
    api: <T>(url: string, options?: RequestInit) => Promise<T>
    pages: PageOption[]
}>()

const tab = ref<'general' | 'smtp' | 'permalinks'>('general')
const siteName = ref('')
const homepageSlug = ref('home')
const mail = ref<MailConfig>({
    host: '',
    port: 587,
    username: '',
    encryption: 'tls',
    from_address: '',
    from_name: '',
})
const mailPassword = ref('')
const testRecipient = ref('')
const saving = ref(false)

function decodeSetting(raw: unknown): string {
    if (typeof raw !== 'string') return ''
    try {
        const decoded = JSON.parse(raw)
        return typeof decoded === 'string' ? decoded : String(decoded ?? '')
    } catch {
        return raw
    }
}

async function load(): Promise<void> {
    try {
        const settings = await props.api<SettingRow[]>('/settings')
        const nameRow = settings.find((row) => row.key === 'site_name')
        const homeRow = settings.find((row) => row.key === 'homepage_slug')
        siteName.value = nameRow ? decodeSetting(nameRow.value) : ''
        homepageSlug.value = homeRow ? decodeSetting(homeRow.value) || 'home' : 'home'
        mail.value = { ...mail.value, ...(await props.api<MailConfig>('/mail')) }
        if (!testRecipient.value && mail.value.from_address) {
            testRecipient.value = mail.value.from_address
        }
    } catch (error) {
        toast.error(error instanceof Error ? error.message : 'Could not load settings')
    }
}

async function saveGeneral(): Promise<void> {
    saving.value = true
    try {
        await props.api('/settings/site_name', {
            method: 'PUT',
            body: JSON.stringify({ value: siteName.value, group: 'general', is_public: true }),
        })
        await props.api('/settings/homepage_slug', {
            method: 'PUT',
            body: JSON.stringify({ value: homepageSlug.value || 'home', group: 'general', is_public: true }),
        })
        toast.success('General settings saved')
    } catch (error) {
        toast.error(error instanceof Error ? error.message : 'Could not save settings')
    } finally {
        saving.value = false
    }
}

async function saveMail(): Promise<void> {
    saving.value = true
    try {
        const payload: Record<string, unknown> = {
            host: mail.value.host,
            port: Number(mail.value.port || 587),
            username: mail.value.username || null,
            encryption: mail.value.encryption || 'tls',
            from_address: mail.value.from_address,
            from_name: mail.value.from_name || siteName.value,
            is_active: true,
        }
        if (mailPassword.value) payload.password = mailPassword.value
        const result = await props.api<{ config: MailConfig }>('/mail', {
            method: 'PUT',
            body: JSON.stringify(payload),
        })
        mail.value = { ...mail.value, ...result.config }
        mailPassword.value = ''
        toast.success('SMTP settings saved')
    } catch (error) {
        toast.error(error instanceof Error ? error.message : 'Could not save SMTP')
    } finally {
        saving.value = false
    }
}

async function sendTest(): Promise<void> {
    if (!testRecipient.value) return
    try {
        await props.api('/mail/test', {
            method: 'POST',
            body: JSON.stringify({ recipient: testRecipient.value }),
        })
        toast.success('Test email sent')
    } catch (error) {
        toast.error(error instanceof Error ? error.message : 'Test email failed')
    }
}

onMounted(load)
</script>

<template>
    <section class="space-y-4">
        <div>
            <h1 class="text-3xl font-semibold tracking-tight">Settings</h1>
            <p class="text-muted-foreground text-sm">Site identity, email delivery, and how page addresses work.</p>
        </div>

        <div class="flex flex-wrap gap-1 rounded-lg border p-1 w-fit">
            <Button size="sm" :variant="tab === 'general' ? 'secondary' : 'ghost'" @click="tab = 'general'">General</Button>
            <Button size="sm" :variant="tab === 'smtp' ? 'secondary' : 'ghost'" @click="tab = 'smtp'">Email / SMTP</Button>
            <Button size="sm" :variant="tab === 'permalinks' ? 'secondary' : 'ghost'" @click="tab = 'permalinks'">Permalinks</Button>
        </div>

        <Card v-if="tab === 'general'" class="max-w-2xl">
            <CardHeader>
                <CardTitle>General</CardTitle>
                <CardDescription>Shown in the public header and browser title.</CardDescription>
            </CardHeader>
            <CardContent class="space-y-4">
                <div class="space-y-2">
                    <Label for="site-name">Site name</Label>
                    <Input id="site-name" v-model="siteName" placeholder="My personal site" />
                </div>
                <div class="space-y-2">
                    <Label for="homepage">Homepage page slug</Label>
                    <select
                        id="homepage"
                        v-model="homepageSlug"
                        class="border-input bg-background flex h-9 w-full rounded-md border px-3 text-sm"
                    >
                        <option value="home">home (recommended)</option>
                        <option v-for="page in pages" :key="page.id" :value="page.slug">
                            {{ page.title }} (/{{ page.slug }})
                        </option>
                    </select>
                    <p class="text-muted-foreground text-xs">
                        Publish a page with slug <code>home</code> (or the slug you choose) to serve it at <code>/</code>.
                    </p>
                </div>
                <Button :disabled="saving" @click="saveGeneral">Save general</Button>
            </CardContent>
        </Card>

        <Card v-else-if="tab === 'smtp'" class="max-w-2xl">
            <CardHeader>
                <CardTitle>SMTP</CardTitle>
                <CardDescription>Used for contact form notifications and test emails.</CardDescription>
            </CardHeader>
            <CardContent class="space-y-4">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="space-y-2 sm:col-span-2">
                        <Label for="smtp-host">Host</Label>
                        <Input id="smtp-host" v-model="mail.host" placeholder="smtp.example.com" />
                    </div>
                    <div class="space-y-2">
                        <Label for="smtp-port">Port</Label>
                        <Input id="smtp-port" v-model.number="mail.port" type="number" />
                    </div>
                    <div class="space-y-2">
                        <Label for="smtp-encryption">Encryption</Label>
                        <select id="smtp-encryption" v-model="mail.encryption" class="border-input bg-background flex h-9 w-full rounded-md border px-3 text-sm">
                            <option value="tls">TLS</option>
                            <option value="ssl">SSL</option>
                            <option value="null">None</option>
                        </select>
                    </div>
                    <div class="space-y-2">
                        <Label for="smtp-user">Username</Label>
                        <Input id="smtp-user" v-model="mail.username" autocomplete="off" />
                    </div>
                    <div class="space-y-2">
                        <Label for="smtp-pass">Password</Label>
                        <Input id="smtp-pass" v-model="mailPassword" type="password" placeholder="Leave blank to keep current" autocomplete="new-password" />
                    </div>
                    <div class="space-y-2">
                        <Label for="from-address">From address</Label>
                        <Input id="from-address" v-model="mail.from_address" type="email" />
                    </div>
                    <div class="space-y-2">
                        <Label for="from-name">From name</Label>
                        <Input id="from-name" v-model="mail.from_name" />
                    </div>
                </div>
                <Button :disabled="saving" @click="saveMail">Save SMTP</Button>
                <div class="flex flex-wrap items-end gap-2 border-t pt-4">
                    <div class="min-w-[220px] flex-1 space-y-2">
                        <Label for="test-email">Send test to</Label>
                        <Input id="test-email" v-model="testRecipient" type="email" />
                    </div>
                    <Button variant="outline" class="gap-2" @click="sendTest">
                        <Send class="size-4" />
                        <span>Send test</span>
                    </Button>
                </div>
            </CardContent>
        </Card>

        <Card v-else class="max-w-2xl">
            <CardHeader>
                <CardTitle>Permalinks</CardTitle>
                <CardDescription>How public page URLs are built.</CardDescription>
            </CardHeader>
            <CardContent class="space-y-3 text-sm">
                <p>
                    Published pages are available at <code class="bg-muted rounded px-1 py-0.5">/{slug}</code>.
                    Example: a page titled “About” with slug <code class="bg-muted rounded px-1 py-0.5">about</code> is at
                    <code class="bg-muted rounded px-1 py-0.5">/about</code>.
                </p>
                <p class="text-muted-foreground">
                    The homepage uses slug <code class="bg-muted rounded px-1 py-0.5">home</code> (or your chosen homepage slug) and is served at <code class="bg-muted rounded px-1 py-0.5">/</code>.
                    WordPress-style pretty-permalink plugins are not needed — this is the only public URL pattern.
                </p>
            </CardContent>
        </Card>
    </section>
</template>
