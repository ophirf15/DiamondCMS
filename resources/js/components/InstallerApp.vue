<script setup lang="ts">
import { computed, reactive } from 'vue'
import { CheckCircle2, CircleAlert, Database, Shield, UserRound } from '@lucide/vue'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Badge } from '@/components/ui/badge'
import { Separator } from '@/components/ui/separator'
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert'

const brandLogo = '/brand/logo-primary-gold.svg'

type Requirements = {
    php: { ok: boolean, current: string, required: string }
    extensions: Record<string, boolean>
    writable: Record<string, boolean>
    rewrite?: { ok: boolean, note?: string }
}

type Boot = {
    csrf: string
    status?: string | null
    errors?: string[]
    requirements: Requirements
    databaseAction: string
    finishAction: string
    recoveryAction: string
    defaults: {
        db_host: string
        db_port: string
        db_database: string
        db_username: string
        db_password: string
        site_name: string
        base_url: string
        admin_name: string
        admin_email: string
    }
}

const props = defineProps<{ boot: Boot }>()

const db = reactive({
    host: props.boot.defaults.db_host,
    port: props.boot.defaults.db_port,
    database: props.boot.defaults.db_database,
    username: props.boot.defaults.db_username,
    password: props.boot.defaults.db_password,
})

const extensionEntries = computed(() => Object.entries(props.boot.requirements.extensions))
const writableEntries = computed(() => Object.entries(props.boot.requirements.writable ?? {}))
</script>

<template>
    <div class="dark mx-auto flex min-h-screen max-w-3xl flex-col gap-6 bg-background px-4 py-10 text-foreground">
        <header class="space-y-3">
            <div class="flex items-center gap-2 text-primary">
                <img :src="brandLogo" alt="" class="size-5 shrink-0">
                <Badge variant="secondary">DiamondCMS installer</Badge>
            </div>
            <h1 class="text-4xl font-semibold tracking-tight">Install DiamondCMS</h1>
            <p class="max-w-2xl text-muted-foreground">
                Guided setup for a shared-host PHP and MySQL install. No shell access required.
            </p>
        </header>

        <Alert v-if="boot.status">
            <CheckCircle2 />
            <AlertTitle>Status</AlertTitle>
            <AlertDescription>{{ boot.status }}</AlertDescription>
        </Alert>

        <Alert v-if="boot.errors?.length" variant="destructive">
            <CircleAlert />
            <AlertTitle>Fix these before continuing</AlertTitle>
            <AlertDescription>
                <ul class="mt-2 list-disc pl-4">
                    <li v-for="error in boot.errors" :key="error">{{ error }}</li>
                </ul>
            </AlertDescription>
        </Alert>

        <Card>
            <CardHeader>
                <CardTitle class="flex items-center gap-2 text-lg">
                    <Shield class="size-4" />
                    1. Requirements
                </CardTitle>
                <CardDescription>Server checks for a shared-host compatible install.</CardDescription>
            </CardHeader>
            <CardContent class="space-y-3">
                <div class="flex items-center justify-between rounded-lg border px-3 py-2 text-sm">
                    <span>PHP {{ boot.requirements.php.current }}</span>
                    <Badge :variant="boot.requirements.php.ok ? 'default' : 'destructive'">
                        {{ boot.requirements.php.ok ? 'OK' : 'Upgrade required' }}
                    </Badge>
                </div>
                <div
                    v-for="[name, ok] in extensionEntries"
                    :key="name"
                    class="flex items-center justify-between rounded-lg border px-3 py-2 text-sm"
                >
                    <span>{{ name }}</span>
                    <Badge :variant="ok ? 'secondary' : 'destructive'">{{ ok ? 'OK' : 'Missing' }}</Badge>
                </div>
                <div
                    v-for="[path, ok] in writableEntries"
                    :key="path"
                    class="flex items-center justify-between rounded-lg border px-3 py-2 text-sm"
                >
                    <span>{{ path }}</span>
                    <Badge :variant="ok ? 'secondary' : 'destructive'">{{ ok ? 'Writable' : 'Fix permissions' }}</Badge>
                </div>
            </CardContent>
        </Card>

        <Card>
            <CardHeader>
                <CardTitle class="flex items-center gap-2 text-lg">
                    <Database class="size-4" />
                    2. Database
                </CardTitle>
                <CardDescription>
                    Use your Bluehost MySQL database (usually prefixed like <code class="rounded bg-muted px-1">ttmrklmy_…</code>).
                    Host is typically <code class="rounded bg-muted px-1">localhost</code>.
                </CardDescription>
            </CardHeader>
            <CardContent>
                <form :action="boot.databaseAction" method="post" class="grid gap-4">
                    <input type="hidden" name="_token" :value="boot.csrf">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="space-y-1.5">
                            <Label for="db_host">Host</Label>
                            <Input id="db_host" name="db_host" v-model="db.host" required />
                        </div>
                        <div class="space-y-1.5">
                            <Label for="db_port">Port</Label>
                            <Input id="db_port" name="db_port" v-model="db.port" required />
                        </div>
                        <div class="space-y-1.5">
                            <Label for="db_database">Database</Label>
                            <Input id="db_database" name="db_database" v-model="db.database" required />
                        </div>
                        <div class="space-y-1.5">
                            <Label for="db_username">Username</Label>
                            <Input id="db_username" name="db_username" v-model="db.username" autocomplete="username" required />
                        </div>
                        <div class="space-y-1.5 sm:col-span-2">
                            <Label for="db_password">Password</Label>
                            <Input id="db_password" name="db_password" v-model="db.password" type="password" autocomplete="new-password" />
                        </div>
                    </div>
                    <Button type="submit" class="w-fit gap-2">
                        <Database />
                        Test database
                    </Button>
                </form>
            </CardContent>
        </Card>

        <Card>
            <CardHeader>
                <CardTitle class="flex items-center gap-2 text-lg">
                    <UserRound class="size-4" />
                    3. Site and administrator
                </CardTitle>
                <CardDescription>Creates the first admin account and locks the installer.</CardDescription>
            </CardHeader>
            <CardContent>
                <form :action="boot.finishAction" method="post" class="grid gap-4">
                    <input type="hidden" name="_token" :value="boot.csrf">
                    <!-- DB credentials must ship with finish — session alone is unreliable on shared hosts. -->
                    <input type="hidden" name="db_host" :value="db.host">
                    <input type="hidden" name="db_port" :value="db.port">
                    <input type="hidden" name="db_database" :value="db.database">
                    <input type="hidden" name="db_username" :value="db.username">
                    <input type="hidden" name="db_password" :value="db.password">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="space-y-1.5">
                            <Label for="site_name">Site name</Label>
                            <Input id="site_name" name="site_name" :default-value="boot.defaults.site_name" required />
                        </div>
                        <div class="space-y-1.5">
                            <Label for="base_url">Base URL</Label>
                            <Input id="base_url" name="base_url" type="url" :default-value="boot.defaults.base_url" required />
                        </div>
                        <div class="space-y-1.5">
                            <Label for="admin_name">Admin name</Label>
                            <Input id="admin_name" name="admin_name" :default-value="boot.defaults.admin_name" required />
                        </div>
                        <div class="space-y-1.5">
                            <Label for="admin_email">Admin email</Label>
                            <Input id="admin_email" name="admin_email" type="email" :default-value="boot.defaults.admin_email" autocomplete="username" required />
                        </div>
                        <div class="space-y-1.5">
                            <Label for="admin_password">Admin password</Label>
                            <Input id="admin_password" name="admin_password" type="password" minlength="12" autocomplete="new-password" required />
                        </div>
                        <div class="space-y-1.5">
                            <Label for="admin_password_confirmation">Confirm password</Label>
                            <Input id="admin_password_confirmation" name="admin_password_confirmation" type="password" minlength="12" autocomplete="new-password" required />
                        </div>
                    </div>
                    <Button type="submit" class="w-fit">Finish installation</Button>
                </form>

                <Separator class="my-6" />

                <details class="space-y-3">
                    <summary class="cursor-pointer text-sm font-medium text-muted-foreground">Install recovery</summary>
                    <p class="text-sm text-muted-foreground">
                        If the installer is locked after a failed run, clear the lock with your recovery key.
                    </p>
                    <form :action="boot.recoveryAction" method="post" class="flex flex-wrap items-end gap-3">
                        <input type="hidden" name="_token" :value="boot.csrf">
                        <div class="min-w-56 flex-1 space-y-1.5">
                            <Label for="recovery_key">Recovery key</Label>
                            <Input id="recovery_key" name="recovery_key" required />
                        </div>
                        <Button type="submit" variant="outline">Clear install lock</Button>
                    </form>
                </details>
            </CardContent>
        </Card>
    </div>
</template>
